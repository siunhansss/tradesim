<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Quản lý người dùng (M10, FR-13): thêm, sửa, khóa/mở, đổi vai trò, xóa có ràng buộc. */
class UserController extends Controller
{
    private const SORTS = ['name', 'email', 'role', 'status', 'created_at'];

    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:'.implode(',', array_column(Role::cases(), 'value'))],
            'status' => ['nullable', 'in:'.implode(',', array_column(UserStatus::cases(), 'value'))],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTS)],
            'dir' => ['nullable', 'in:asc,desc'],
        ]);

        $users = User::query()
            ->with('wallet')
            ->withCount('orders')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%')))
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['dir'] ?? 'desc')
            ->orderByDesc('id')
            ->paginate(config('tradesim.per_page'))
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'sort' => $filters['sort'] ?? 'created_at',
            'dir' => $filters['dir'] ?? 'desc',
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', ['user' => new User(['role' => Role::Trader, 'status' => UserStatus::Active])]);
    }

    public function store(UserRequest $request, WalletService $wallets): RedirectResponse
    {
        $user = DB::transaction(function () use ($request, $wallets) {
            $user = User::query()->create($request->validated());
            // Nhà đầu tư mới được cấp vốn khởi tạo như khi tự đăng ký (BR-01).
            if ($user->isTrader()) {
                $wallets->openWithInitialCapital($user);
            }
            $this->audit->log('user.create', $user, 'ok', Arr::except($request->validated(), ['password']));

            return $user;
        });

        return redirect()->route('admin.users.index')->with('success', "Đã tạo tài khoản {$user->email}.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(UserRequest $request, User $user, WalletService $wallets): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->fill($data);
        $changes = array_keys($user->getDirty());
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        // Đổi sang nhà đầu tư mà chưa có ví thì mở ví và cấp vốn (an toàn khi gọi lại).
        if ($user->isTrader()) {
            $wallets->openWithInitialCapital($user);
        }

        if ($changes) {
            $this->audit->log('user.update', $user, 'ok', ['fields' => $changes]);
        }

        return redirect()->route('admin.users.index')->with('success', "Đã cập nhật tài khoản {$user->email}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Bạn không thể xóa tài khoản của chính mình.');
        }
        // Giữ lịch sử giao dịch (BR-06): tài khoản đã giao dịch chỉ được khóa, không xóa.
        if ($user->orders()->exists() || $user->trades()->exists()) {
            $this->audit->log('user.delete', $user, 'denied', ['reason' => 'Đã có lịch sử giao dịch']);

            return back()->with('error', "Không thể xóa {$user->email} vì tài khoản đã có lệnh hoặc giao dịch. Hãy chuyển trạng thái sang “Bị khóa”.");
        }

        $email = $user->email;
        $this->audit->log('user.delete', $user, 'ok', ['email' => $email]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "Đã xóa tài khoản {$email}.");
    }
}
