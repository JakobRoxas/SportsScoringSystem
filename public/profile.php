<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
  header('Location: /sports/login.php');
  exit;
}

$usersFile = __DIR__ . '/../data/users.json';
$users = json_decode(file_get_contents($usersFile), true);
$currentUser = null;
$userIndex = null;

foreach ($users as $index => $user) {
  if ($user['id'] == $_SESSION['user_id']) {
    $currentUser = $user;
    $userIndex = $index;
    break;
  }
}

if (!$currentUser) {
  header('Location: /sports/logout.php');
  exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $currentPassword = $_POST['current_password'] ?? '';
  $newPassword = $_POST['new_password'] ?? '';
  $confirmPassword = $_POST['confirm_password'] ?? '';
  
  if (!$fullName || !$email) {
    $error = 'Full name and email are required';
  } else {
    // Check if email is taken by another user
    foreach ($users as $user) {
      if ($user['id'] != $_SESSION['user_id'] && $user['email'] === $email) {
        $error = 'Email already in use by another account';
        break;
      }
    }
    
    if (!$error) {
      $users[$userIndex]['full_name'] = $fullName;
      $users[$userIndex]['email'] = $email;
      $_SESSION['full_name'] = $fullName;
      $_SESSION['email'] = $email;
      
      if ($newPassword) {
        if (!password_verify($currentPassword, $currentUser['password'])) {
          $error = 'Current password is incorrect';
        } elseif ($newPassword !== $confirmPassword) {
          $error = 'New passwords do not match';
        } elseif (strlen($newPassword) < 6) {
          $error = 'Password must be at least 6 characters';
        } else {
          $users[$userIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
          $success = 'Profile and password updated successfully!';
        }
      } else {
        $success = 'Profile updated successfully!';
      }
      
      if (!$error) {
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        $currentUser = $users[$userIndex];
      }
    }
  }
}

include __DIR__ . '/header.php';
?>

<div class="content-wrapper">
  <h2 class="mb-4"><i class="fas fa-user-cog me-2"></i>Edit Profile</h2>

  <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-user me-2"></i>Account Information</h5>
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <div class="mb-3">
              <label for="username" class="form-label">Username</label>
              <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($currentUser['username']) ?>" disabled>
              <small class="text-muted">Username cannot be changed</small>
            </div>
            
            <div class="mb-3">
              <label for="full_name" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($currentUser['full_name']) ?>" required>
            </div>
            
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($currentUser['email']) ?>" required>
            </div>
            
            <hr class="my-4">
            
            <h6 class="mb-3">Change Password (Optional)</h6>
            <p class="text-muted small">Leave blank to keep current password</p>
            
            <div class="mb-3">
              <label for="current_password" class="form-label">Current Password</label>
              <input type="password" class="form-control" id="current_password" name="current_password">
            </div>
            
            <div class="mb-3">
              <label for="new_password" class="form-label">New Password</label>
              <input type="password" class="form-control" id="new_password" name="new_password">
              <small class="text-muted">At least 6 characters</small>
            </div>
            
            <div class="mb-3">
              <label for="confirm_password" class="form-label">Confirm New Password</label>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password">
            </div>
            
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Changes
              </button>
              <a href="/sports/public/dashboard.php" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i>Cancel
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Details</h5>
        </div>
        <div class="card-body">
          <p><strong>Role:</strong> <span class="badge bg-primary"><?= ucfirst($currentUser['role']) ?></span></p>
          <p><strong>Favorite Players:</strong> <?= count($currentUser['favorites'] ?? []) ?></p>
          <hr>
          <a href="/sports/public/favorites.php" class="btn btn-outline-primary w-100 mb-2">
            <i class="fas fa-star me-2"></i>Manage Favorites
          </a>
          <a href="/sports/logout.php" class="btn btn-outline-danger w-100">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
