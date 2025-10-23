<?php
/**
 * facebook_demo.php — Single-file advanced demo social network (PHP + SQLite)
 *
 * Features included (demo-level):
 * - SQLite DB auto-create (social_full.db)
 * - Register / Login / Logout (password_hash)
 * - CSRF tokens for POST forms
 * - Profiles with avatar upload & bio
 * - Posts (text + image) with privacy (public / friends)
 * - Comments & Likes (AJAX-ish)
 * - Friend requests: send / accept / reject / unfriend
 * - Friends list / people suggestions
 * - Direct messages (simple inbox)
 * - Groups: create / join / post (group posts appear to members)
 * - Pages: create / follow / post (page posts appear to followers)
 * - Events: create / RSVP (yes/maybe/no)
 * - Notifications (create & mark seen)
 * - Admin panel (seed admin during first run)
 * - Search users & posts
 * - Basic feed: your posts + friends + groups + pages + public posts
 *
 * DEMO ONLY: Not production-ready. Do not use to host real users without hardening.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

/* ========== CONFIG ========== */
$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'social_db';

define('UPLOAD_DIR', __DIR__.'/uploads');
@mkdir(UPLOAD_DIR, 0755, true);
$dbfile = __DIR__ . '/social_full.db';

session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['csrf'];

/* ---------- DB ---------- */
$pdo = new PDO('sqlite:'.$dbfile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
/* ========== CONNECT + CREATE DB ========== */
try {
    $dsnNoDb = "mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4";
    $pdo0 = new PDO($dsnNoDb, $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo0->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) { die("DB server connection failed: ".htmlspecialchars($e->getMessage())); }

try {
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) { die("DB connect failed: ".htmlspecialchars($e->getMessage())); }

/* ---------- SCHEMA ---------- */
$create = [
"CREATE TABLE IF NOT EXISTS users (
id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  display_name TEXT,
  bio TEXT,
  avatar TEXT,
  role TEXT DEFAULT 'user',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);",
"CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT(3) NOT NULL,
  content TEXT,
  image TEXT,
  privacy TEXT DEFAULT 'public', -- public, friends
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  content TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS likes (
  id INT AUTO_INCREMENT PRIMARY KEY  ,
  post_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(post_id, user_id),
  FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS friend_requests (
  id INT AUTO_INCREMENT PRIMARY KEY ,
  from_user INTEGER NOT NULL,
  to_user INTEGER NOT NULL,
  status TEXT DEFAULT 'pending', -- pending, accepted, rejected
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(from_user, to_user),
  FOREIGN KEY(from_user) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(to_user) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS friends (
  id INT AUTO_INCREMENT PRIMARY KEY ,
  user_a INTEGER NOT NULL,
  user_b INTEGER NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(user_a, user_b),
  FOREIGN KEY(user_a) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(user_b) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY ,
  from_user INTEGER NOT NULL,
  to_user INTEGER NOT NULL,
  body TEXT NOT NULL,
  seen INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(from_user) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(to_user) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS groups (
  id INT AUTO_INCREMENT PRIMARY KEY  ,
  name TEXT NOT NULL,
  description TEXT,
  owner INTEGER NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(owner) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS group_members (
  id INT AUTO_INCREMENT PRIMARY KEY ,
  group_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  role TEXT DEFAULT 'member', -- owner, admin, member
  joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(group_id, user_id),
  FOREIGN KEY(group_id) REFERENCES groups(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS group_posts (
  id INT AUTO_INCREMENT PRIMARY KEY  ,
  group_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  content TEXT,
  image TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(group_id) REFERENCES groups(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS pages (
  id INT AUTO_INCREMENT PRIMARY KEY ,
  title TEXT NOT NULL,
  description TEXT,
  owner INTEGER NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(owner) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS page_followers (
  id INT AUTO_INCREMENT PRIMARY KEY ,
  page_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(page_id, user_id),
  FOREIGN KEY(page_id) REFERENCES pages(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS page_posts (
  id INT AUTO_INCREMENT PRIMARY KEY ,
  page_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  content TEXT,
  image TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(page_id) REFERENCES pages(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY ,
  title TEXT NOT NULL,
  description TEXT,
  location TEXT,
  starts_at DATETIME,
  ends_at DATETIME,
  owner INTEGER NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(owner) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS event_responses (
  id INT AUTO_INCREMENT PRIMARY KEY  ,
  event_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  response TEXT DEFAULT 'maybe', -- yes, maybe, no
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(event_id, user_id),
  FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);",
"CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY ,
  user_id INTEGER NOT NULL,
  content TEXT NOT NULL,
  seen INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);"
];


foreach ($create as $sql) {
    try { $pdo->exec($sql); } catch (Exception $e) { die("DB create error: ".htmlspecialchars($e->getMessage())); }
}

/* Seed admin user if none */
$adminCheck = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
if (!$adminCheck) {
    $password = password_hash('Admin123!', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username,email,password,display_name,role) VALUES (?,?,?,?, 'admin')");
    $stmt->execute(['admin','admin@example.com',$password,'Administrator']);
}

/* ---------- Helpers ---------- */
function me_id(){ return $_SESSION['user_id'] ?? null; }
function logged_in(){ return !empty($_SESSION['user_id']); }
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function check_csrf($t){ return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t); }
function upload_file($file, $prefix='file'){
    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($mime, $allowed)) return null;
    if ($file['size'] > 6*1024*1024) return null;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $name = $prefix.'_'.bin2hex(random_bytes(8)).'.'.$ext;
    $dest = UPLOAD_DIR.'/'.$name;
    if (move_uploaded_file($file['tmp_name'], $dest)) return $name;
    return null;
}
function notify_user($pdo, $user_id, $content){
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, content) VALUES (?,?)");
    $stmt->execute([$user_id, $content]);
}

/* ---------- POST HANDLERS ---------- */
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'register') {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            if (!$username || !$email || !$password) throw new Exception('Complete fields');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email');
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username,email,password,display_name) VALUES (?,?,?,?)");
            $stmt->execute([$username,$email,$hash,$username]);
            $uid = $pdo->lastInsertId();
            $_SESSION['user_id'] = $uid; $_SESSION['username'] = $username;
            $_SESSION['flash'] = 'Registered';
            header('Location: '.$_SERVER['PHP_SELF']); exit;
        }

        if ($action === 'login') {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1"); $stmt->execute([$email]); $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$u || !password_verify($password, $u['password'])) throw new Exception('Invalid credentials');
            session_regenerate_id(true);
            $_SESSION['user_id'] = $u['id']; $_SESSION['username'] = $u['username'];
            $_SESSION['flash'] = 'Welcome back';
            header('Location: '.$_SERVER['PHP_SELF']); exit;
        }

        if ($action === 'logout') {
            session_unset(); session_destroy(); session_start();
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
            $_SESSION['flash'] = 'Logged out';
            header('Location: '.$_SERVER['PHP_SELF']); exit;
        }

        if ($action === 'update_profile' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $display = trim($_POST['display_name'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            $avatar = null;
            if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) $avatar = upload_file($_FILES['avatar'],'avatar');
            if ($avatar) {
                $stmt = $pdo->prepare("UPDATE users SET display_name=?, bio=?, avatar=? WHERE id=?");
                $stmt->execute([$display,$bio,$avatar, me_id()]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET display_name=?, bio=? WHERE id=?");
                $stmt->execute([$display,$bio, me_id()]);
            }
            $_SESSION['flash'] = 'Profile updated';
            header('Location: ?view=profile'); exit;
        }

        if ($action === 'create_post' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $content = trim($_POST['content'] ?? '');
            $privacy = in_array($_POST['privacy'] ?? 'public', ['public','friends']) ? $_POST['privacy'] : 'public';
            $img = null;
            if (!empty($_FILES['image']) && $_FILES['image']['error']===UPLOAD_ERR_OK) $img = upload_file($_FILES['image'],'post');
            if ($content === '' && !$img) throw new Exception('Post empty');
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image, privacy) VALUES (?,?,?,?)");
            $stmt->execute([me_id(), $content, $img, $privacy]);
            // notify friends if friends-only or public (demo sends a generic notification)
            // fetch friends
            $fstmt = $pdo->prepare("SELECT CASE WHEN user_a = ? THEN user_b ELSE user_a END AS uid FROM friends WHERE user_a = ? OR user_b = ?");
            $fstmt->execute([me_id(), me_id(), me_id()]);
            $friends = $fstmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($friends as $fid) notify_user($pdo, $fid, "Your friend {$_SESSION['username']} posted.");
            $_SESSION['flash'] = 'Posted';
            header('Location: '.$_SERVER['PHP_SELF']); exit;
        }

        if ($action === 'comment' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $post_id = (int)($_POST['post_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            if (!$post_id || $content==='') throw new Exception('Invalid comment');
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?,?,?)");
            $stmt->execute([$post_id, me_id(), $content]);
            // notify post owner
            $p = $pdo->prepare("SELECT user_id FROM posts WHERE id = ? LIMIT 1"); $p->execute([$post_id]); $owner = $p->fetchColumn();
            if ($owner && $owner != me_id()) notify_user($pdo, $owner, "{$_SESSION['username']} commented on your post.");
            $_SESSION['flash'] = 'Comment posted';
            header('Location: '.$_SERVER['PHP_SELF']); exit;
        }

        if ($action === 'send_friend' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $to = (int)($_POST['to_user'] ?? 0);
            if (!$to || $to == me_id()) throw new Exception('Invalid user');
            // insert friend request if not exists and not already friends
            $check = $pdo->prepare("SELECT id FROM friend_requests WHERE from_user=? AND to_user=? LIMIT 1"); $check->execute([me_id(), $to]);
            if ($check->fetch()) throw new Exception('Already requested');
            // also ensure not friends
            $a=min(me_id(),$to); $b=max(me_id(),$to);
            $fch = $pdo->prepare("SELECT id FROM friends WHERE user_a=? AND user_b=? LIMIT 1"); $fch->execute([$a,$b]);
            if ($fch->fetch()) throw new Exception('Already friends');
            $ins = $pdo->prepare("INSERT INTO friend_requests (from_user, to_user) VALUES (?,?)"); $ins->execute([me_id(), $to]);
            notify_user($pdo, $to, "{$_SESSION['username']} sent you a friend request.");
            $_SESSION['flash'] = 'Friend request sent';
            header('Location: ?view=profile&uid='.$to); exit;
        }

        if ($action === 'respond_friend' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $req_id = (int)($_POST['req_id'] ?? 0);
            $resp = $_POST['response'] ?? 'reject';
            $r = $pdo->prepare("SELECT * FROM friend_requests WHERE id = ? LIMIT 1"); $r->execute([$req_id]); $req = $r->fetch(PDO::FETCH_ASSOC);
            if (!$req) throw new Exception('Bad request');
            if ($req['to_user'] != me_id()) throw new Exception('Not allowed');
            if ($resp === 'accept') {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE friend_requests SET status='accepted' WHERE id=?")->execute([$req_id]);
                $a=min($req['from_user'],$req['to_user']); $b=max($req['from_user'],$req['to_user']);
                $pdo->prepare("INSERT OR IGNORE INTO friends (user_a, user_b) VALUES (?,?)")->execute([$a,$b]);
                notify_user($pdo, $req['from_user'], "{$_SESSION['username']} accepted your friend request.");
                $pdo->commit();
                $_SESSION['flash'] = 'Friend added';
            } else {
                $pdo->prepare("UPDATE friend_requests SET status='rejected' WHERE id=?")->execute([$req_id]);
                $_SESSION['flash'] = 'Friend request rejected';
            }
            header('Location: '.$_SERVER['PHP_SELF']); exit;
        }

        if ($action === 'unfriend' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $other = (int)($_POST['other_user'] ?? 0);
            if (!$other) throw new Exception('Bad user');
            $a=min($other, me_id()); $b=max($other, me_id());
            $pdo->prepare("DELETE FROM friends WHERE user_a=? AND user_b=?")->execute([$a,$b]);
            $_SESSION['flash'] = 'Unfriended';
            header('Location: ?view=profile&uid='.$other); exit;
        }

        if ($action === 'toggle_like' && logged_in()) {
            // AJAX friendly: returns plain result
            if (!check_csrf($_POST['csrf'] ?? '')) { http_response_code(400); echo "bad_csrf"; exit; }
            $post_id = (int)($_POST['post_id'] ?? 0);
            if (!$post_id) { http_response_code(400); echo "bad_post"; exit; }
            $chk = $pdo->prepare("SELECT id FROM likes WHERE post_id=? AND user_id=? LIMIT 1"); $chk->execute([$post_id, me_id()]);
            if ($chk->fetch()) {
                $pdo->prepare("DELETE FROM likes WHERE post_id=? AND user_id=?")->execute([$post_id, me_id()]);
                echo "unliked"; exit;
            } else {
                $pdo->prepare("INSERT OR IGNORE INTO likes (post_id, user_id) VALUES (?,?)")->execute([$post_id, me_id()]);
                // notify owner
                $p = $pdo->prepare("SELECT user_id FROM posts WHERE id=? LIMIT 1"); $p->execute([$post_id]); $owner = $p->fetchColumn();
                if ($owner && $owner != me_id()) notify_user($pdo, $owner, "{$_SESSION['username']} liked your post.");
                echo "liked"; exit;
            }
        }

        if ($action === 'send_message' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $to = (int)($_POST['to_user'] ?? 0);
            $body = trim($_POST['body'] ?? '');
            if (!$to || $body === '') throw new Exception('Invalid message');
            $pdo->prepare("INSERT INTO messages (from_user, to_user, body) VALUES (?,?,?)")->execute([me_id(), $to, $body]);
            notify_user($pdo, $to, "New message from {$_SESSION['username']}");
            $_SESSION['flash'] = 'Message sent';
            header('Location: ?view=messages&u='.$to); exit;
        }

        if ($action === 'create_group' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $name = trim($_POST['name'] ?? ''); $desc = trim($_POST['description'] ?? '');
            if (!$name) throw new Exception('Name required');
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO groups (name, description, owner) VALUES (?, ?, ?)")->execute([$name,$desc,me_id()]);
            $gid = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?,?, 'owner')")->execute([$gid, me_id()]);
            $pdo->commit();
            $_SESSION['flash'] = 'Group created';
            header('Location: ?view=group&gid='.$gid); exit;
        }

        if ($action === 'join_group' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $gid = (int)($_POST['group_id'] ?? 0);
            if (!$gid) throw new Exception('Bad group');
            $pdo->prepare("INSERT OR IGNORE INTO group_members (group_id, user_id) VALUES (?,?)")->execute([$gid, me_id()]);
            $_SESSION['flash'] = 'Joined group';
            header('Location: ?view=group&gid='.$gid); exit;
        }

        if ($action === 'group_post' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $gid = (int)($_POST['group_id'] ?? 0);
            $content = trim($_POST['content'] ?? ''); $img=null;
            if (!empty($_FILES['group_image']) && $_FILES['group_image']['error']===UPLOAD_ERR_OK) $img = upload_file($_FILES['group_image'],'gpost');
            if (!$gid || ($content==='' && !$img)) throw new Exception('Empty');
            // ensure member?
            $m = $pdo->prepare("SELECT id FROM group_members WHERE group_id=? AND user_id=? LIMIT 1"); $m->execute([$gid, me_id()]);
            if (!$m->fetch()) throw new Exception('Not a member');
            $pdo->prepare("INSERT INTO group_posts (group_id, user_id, content, image) VALUES (?,?,?,?)")->execute([$gid, me_id(), $content, $img]);
            // notify members (simple)
            $mems = $pdo->prepare("SELECT user_id FROM group_members WHERE group_id=?"); $mems->execute([$gid]); $memsList = $mems->fetchAll(PDO::FETCH_COLUMN);
            foreach ($memsList as $uid) if ($uid != me_id()) notify_user($pdo, $uid, "{$_SESSION['username']} posted in a group you follow.");
            $_SESSION['flash'] = 'Posted in group';
            header('Location: ?view=group&gid='.$gid); exit;
        }

        if ($action === 'create_page' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $title = trim($_POST['title'] ?? ''); $desc = trim($_POST['description'] ?? '');
            if (!$title) throw new Exception('Title required');
            $pdo->prepare("INSERT INTO pages (title, description, owner) VALUES (?,?,?)")->execute([$title,$desc,me_id()]);
            $pid = $pdo->lastInsertId();
            $_SESSION['flash'] = 'Page created';
            header('Location: ?view=page&pid='.$pid); exit;
        }

        if ($action === 'follow_page' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $pid = (int)($_POST['page_id'] ?? 0);
            if (!$pid) throw new Exception('Bad page');
            $pdo->prepare("INSERT OR IGNORE INTO page_followers (page_id, user_id) VALUES (?,?)")->execute([$pid, me_id()]);
            $_SESSION['flash'] = 'Following page';
            header('Location: ?view=page&pid='.$pid); exit;
        }

        if ($action === 'page_post' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $pid = (int)($_POST['page_id'] ?? 0);
            $content = trim($_POST['content'] ?? ''); $img=null;
            if (!empty($_FILES['page_image']) && $_FILES['page_image']['error']===UPLOAD_ERR_OK) $img = upload_file($_FILES['page_image'],'ppost');
            if (!$pid || ($content==='' && !$img)) throw new Exception('Empty');
            // only owner or admin can post (demo: only owner)
            $o = $pdo->prepare("SELECT owner FROM pages WHERE id=? LIMIT 1"); $o->execute([$pid]); $owner = $o->fetchColumn();
            if ($owner != me_id()) throw new Exception('Not allowed to post on this page');
            $pdo->prepare("INSERT INTO page_posts (page_id, user_id, content, image) VALUES (?,?,?,?)")->execute([$pid, me_id(), $content, $img]);
            // notify followers
            $f = $pdo->prepare("SELECT user_id FROM page_followers WHERE page_id=?"); $f->execute([$pid]); $followers = $f->fetchAll(PDO::FETCH_COLUMN);
            foreach ($followers as $uid) if ($uid != me_id()) notify_user($pdo, $uid, "New post on page you follow.");
            $_SESSION['flash'] = 'Published';
            header('Location: ?view=page&pid='.$pid); exit;
        }

        if ($action === 'create_event' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $title = trim($_POST['title'] ?? ''); $desc = trim($_POST['description'] ?? '');
            $loc = trim($_POST['location'] ?? ''); $starts = trim($_POST['starts_at'] ?? null); $ends = trim($_POST['ends_at'] ?? null);
            if (!$title || !$starts) throw new Exception('Required');
            $pdo->prepare("INSERT INTO events (title, description, location, starts_at, ends_at, owner) VALUES (?,?,?,?,?,?)")
                ->execute([$title,$desc,$loc,$starts,$ends, me_id()]);
            $_SESSION['flash'] = 'Event created';
            header('Location: ?view=events'); exit;
        }

        if ($action === 'rsvp_event' && logged_in()) {
            if (!check_csrf($_POST['csrf'] ?? '')) throw new Exception('Invalid CSRF');
            $eid = (int)($_POST['event_id'] ?? 0); $resp = in_array($_POST['response'] ?? '', ['yes','maybe','no']) ? $_POST['response'] : 'maybe';
            if (!$eid) throw new Exception('Bad event');
            $pdo->prepare("INSERT OR REPLACE INTO event_responses (event_id, user_id, response, created_at) VALUES (?,?,?,datetime('now'))")
                ->execute([$eid, me_id(), $resp]);
            $_SESSION['flash'] = 'RSVP recorded';
            header('Location: ?view=event&eid='.$eid); exit;
        }

        if ($action === 'mark_notifications_seen' && logged_in()) {
            $pdo->prepare("UPDATE notifications SET seen = 1 WHERE user_id = ?")->execute([me_id()]);
            header('Location: ?view=notifications'); exit;
        }

        // Add more actions as needed...
    } catch (Exception $e) {
        // Demo: put message in flash and redirect back
        $_SESSION['flash'] = 'Error: '.$e->getMessage();
        header('Location: '.$_SERVER['PHP_SELF']); exit;
    }
}

/* ---------- Helper queries for rendering ---------- */
$me = null;
if (logged_in()) {
    $me = $pdo->prepare("SELECT id, username, display_name, bio, avatar, role FROM users WHERE id = ? LIMIT 1");
    $me->execute([me_id()]); $me = $me->fetch(PDO::FETCH_ASSOC);
}

$view = $_GET['view'] ?? 'home';

/* fetch notifications */
$notifications = [];
if (logged_in()) {
    $nq = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $nq->execute([me_id()]); $notifications = $nq->fetchAll(PDO::FETCH_ASSOC);
}

/* incoming friend requests */
$incoming_reqs = [];
if (logged_in()) {
    $rq = $pdo->prepare("SELECT fr.*, u.username, u.display_name FROM friend_requests fr JOIN users u ON fr.from_user = u.id WHERE fr.to_user = ? AND fr.status='pending' ORDER BY fr.created_at DESC");
    $rq->execute([me_id()]); $incoming_reqs = $rq->fetchAll(PDO::FETCH_ASSOC);
}

/* feed: your posts + friends + group posts (members) + page posts + public posts (simple union) */
function fetch_feed($pdo) {
    $me = me_id();
    // friends
    $friendsSub = "SELECT CASE WHEN user_a = :me THEN user_b ELSE user_a END FROM friends WHERE user_a = :me OR user_b = :me";
    // groups ids
    $groupSub = "SELECT group_id FROM group_members WHERE user_id = :me";
    // pages followed
    $pageSub = "SELECT page_id FROM page_followers WHERE user_id = :me";

    // Fetch posts from posts table where:
    // - user is me
    // - OR user in friends and privacy in ('friends','public')
    // - OR public posts by anyone
    // Also union group_posts for groups I'm a member and page_posts for pages I follow.
    $sql = "
      SELECT p.id as id, p.user_id as user_id, p.content as content, p.image as image, p.privacy as privacy, p.created_at as created_at, u.username as username, u.display_name as display_name, u.avatar as avatar, 'post' as type
      FROM posts p JOIN users u ON p.user_id = u.id
      WHERE (p.user_id = :me) OR (p.privacy = 'public') OR (p.user_id IN ($friendsSub) AND p.privacy IN ('friends', 'public'))
      UNION
      SELECT gp.id+1000000 as id, gp.user_id as user_id, gp.content as content, gp.image as image, 'group' as privacy, gp.created_at as created_at, u.username as username, u.display_name as display_name, u.avatar as avatar, 'gpost' as type
      FROM group_posts gp JOIN users u ON gp.user_id = u.id
      WHERE gp.group_id IN ($groupSub)
      UNION
      SELECT pp.id+2000000 as id, pp.user_id as user_id, pp.content as content, pp.image as image, 'page' as privacy, pp.created_at as created_at, u.username as username, u.display_name as display_name, u.avatar as avatar, 'ppost' as type
      FROM page_posts pp JOIN users u ON pp.user_id = u.id
      WHERE pp.page_id IN ($pageSub)
      ORDER BY created_at DESC
      LIMIT 200
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':me'=>$me, ':me'=>$me, ':me'=>$me]); // repeated param names are allowed in PDO for SQLite
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$feed_posts = logged_in() ? fetch_feed($pdo) : $pdo->query("SELECT p.*, u.username, u.display_name, u.avatar FROM posts p JOIN users u ON p.user_id = u.id WHERE p.privacy='public' ORDER BY p.created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Render HTML UI (simplified but functional) ---------- */
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Social Demo — Advanced Single File</title>
<style>
:root{--bg:#f5f7fb;--card:#fff;--accent:#1877f2;--muted:#6b7280}
*{box-sizing:border-box}
body{font-family:Inter,system-ui,Arial,sans-serif;background:var(--bg);margin:0;color:#111}
.header{background:#0b1220;color:#fff;padding:10px 16px;display:flex;align-items:center;justify-content:space-between}
.brand{font-weight:800}
.nav a{color:#fff;margin-left:12px;text-decoration:none}
.container{max-width:1100px;margin:18px auto;padding:12px}
.grid{display:grid;grid-template-columns:1fr 320px;gap:16px}
.card{background:var(--card);border-radius:10px;padding:12px;box-shadow:0 6px 18px rgba(2,6,23,0.06)}
.btn{background:var(--accent);color:#fff;border:0;padding:8px 12px;border-radius:8px;cursor:pointer}
.input{width:100%;padding:8px;border-radius:8px;border:1px solid #e6edf3;margin-top:6px}
.small{font-size:13px;color:var(--muted)}
.avatar{width:48px;height:48px;border-radius:999px;object-fit:cover}
.post{border-bottom:1px solid #f0f2f5;padding:12px 0}
.post:last-child{border-bottom:0}
.actions button{background:transparent;border:0;color:var(--accent);cursor:pointer;padding:6px;margin-right:6px}
.linklike{color:var(--accent);text-decoration:none}
.notice{background:#fff8e6;padding:10px;border-left:4px solid #ffd66b;border-radius:6px;margin-bottom:10px}
.badge{background:#eef3ff;color:#0b3b8c;padding:6px 10px;border-radius:999px;font-size:13px}
.table{width:100%;border-collapse:collapse}
.table td,.table th{padding:8px;border-bottom:1px solid #f0f2f5}
.searchbox{display:flex;gap:8px}
.searchbox input{flex:1}
</style>
</head>
<body>
  <header class="header">
    <div style="display:flex;align-items:center;gap:18px">
      <div class="brand">SocialDemo</div>
      <nav>
        <a class="nav" href="?">Home</a>
        <a class="nav" href="?view=explore">Explore</a>
        <?php if(logged_in()): ?>
          <a class="nav" href="?view=messages">Messages</a>
          <a class="nav" href="?view=groups">Groups</a>
          <a class="nav" href="?view=pages">Pages</a>
          <a class="nav" href="?view=events">Events</a>
        <?php endif; ?>
      </nav>
    </div>
    <div>
      <?php if(logged_in()): ?>
        <a href="?view=notifications" class="linklike">Notifications <?= count(array_filter($notifications, fn($n)=> !$n['seen'])) ? "<span class='badge'>".count(array_filter($notifications, fn($n)=> !$n['seen']))."</span>" : '' ?></a>
        <a class="nav" href="?view=profile"><?=esc($me['display_name'] ?? $me['username'])?></a>
        <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="logout"><button class="btn">Logout</button></form>
      <?php else: ?>
        <a class="nav" href="?view=login">Login</a>
        <a class="nav" href="?view=register">Register</a>
      <?php endif; ?>
    </div>
  </header>

  <main class="container">
    <?php if(!empty($flash)): ?><div class="card notice"><?=esc($flash)?></div><?php endif; ?>

    <?php if($view === 'register'): ?>
      <div class="card" style="max-width:560px;margin:0 auto">
        <h2>Sign up</h2>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=esc($csrf)?>">
          <input type="hidden" name="action" value="register">
          <label class="small">Username<input class="input" name="username" required></label>
          <label class="small">Email<input class="input" name="email" type="email" required></label>
          <label class="small">Password<input class="input" name="password" type="password" required></label>
          <div style="text-align:right"><button class="btn">Create account</button></div>
        </form>
      </div>

    <?php elseif($view === 'login'): ?>
      <div class="card" style="max-width:560px;margin:0 auto">
        <h2>Login</h2>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=esc($csrf)?>">
          <input type="hidden" name="action" value="login">
          <label class="small">Email<input class="input" name="email" type="email" required></label>
          <label class="small">Password<input class="input" name="password" type="password" required></label>
          <div style="text-align:right"><button class="btn">Login</button></div>
        </form>
      </div>

    <?php elseif($view === 'profile' && logged_in()): ?>
      <?php
        $uid = (int)($_GET['uid'] ?? me_id());
        $pstmt = $pdo->prepare("SELECT id,username,display_name,bio,avatar,created_at FROM users WHERE id=? LIMIT 1");
        $pstmt->execute([$uid]); $profile = $pstmt->fetch(PDO::FETCH_ASSOC);
        if (!$profile) { echo "<div class='card'>User not found</div>"; }
      ?>
      <div class="grid">
        <section class="card">
          <div style="display:flex;gap:12px;align-items:center">
            <img class="avatar" src="<?= $profile['avatar'] ? 'uploads/'.esc($profile['avatar']) : 'https://via.placeholder.com/80?text=Avatar' ?>" style="width:80px;height:80px">
            <div>
              <div style="font-weight:800"><?=esc($profile['display_name'] ?? $profile['username'])?></div>
              <div class="small"><?=esc($profile['bio'])?></div>
              <div class="small">Joined <?=esc($profile['created_at'])?></div>
            </div>
          </div>

          <?php if($uid == me_id()): ?>
            <h3 style="margin-top:12px">Edit profile</h3>
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="csrf" value="<?=esc($csrf)?>">
              <input type="hidden" name="action" value="update_profile">
              <label class="small">Display name<input class="input" name="display_name" value="<?=esc($profile['display_name'])?>"></label>
              <label class="small">Bio<textarea class="input" name="bio"><?=esc($profile['bio'])?></textarea></label>
              <label class="small">Avatar<input type="file" name="avatar" accept="image/*"></label>
              <div style="text-align:right"><button class="btn">Save</button></div>
            </form>
          <?php else: ?>
            <?php if(logged_in()): ?>
              <?php
                // check friendship
                $a=min(me_id(),$uid); $b=max(me_id(),$uid);
                $isFriend = $pdo->prepare("SELECT id FROM friends WHERE user_a=? AND user_b=? LIMIT 1"); $isFriend->execute([$a,$b]); $isFriend = (bool)$isFriend->fetchColumn();
                $reqSent = $pdo->prepare("SELECT id FROM friend_requests WHERE from_user=? AND to_user=? AND status='pending' LIMIT 1"); $reqSent->execute([me_id(), $uid]); $reqSent = (bool)$reqSent->fetchColumn();
              ?>
              <?php if($isFriend): ?>
                <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="unfriend"><input type="hidden" name="other_user" value="<?=$uid?>"><button class="btn">Unfriend</button></form>
              <?php elseif($reqSent): ?>
                <button class="small" disabled>Request sent</button>
              <?php else: ?>
                <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="send_friend"><input type="hidden" name="to_user" value="<?=$uid?>"><button class="btn">Add Friend</button></form>
              <?php endif; ?>
            <?php endif; ?>
          <?php endif; ?>

          <h3 style="margin-top:12px">Posts</h3>
          <?php
            // fetch posts by user (respect privacy)
            if ($uid == me_id()) {
                $pp = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 100"); $pp->execute([$uid]);
            } else {
                // public + friends if we are friends
                $a=min(me_id(),$uid); $b=max(me_id(),$uid);
                $friendCheck = $pdo->prepare("SELECT id FROM friends WHERE user_a=? AND user_b=? LIMIT 1"); $friendCheck->execute([$a,$b]); $isF = (bool)$friendCheck->fetchColumn();
                if ($isF) {
                    $pp = $pdo->prepare("SELECT * FROM posts WHERE user_id=? AND privacy IN ('friends','public') ORDER BY created_at DESC LIMIT 100"); $pp->execute([$uid]);
                } else {
                    $pp = $pdo->prepare("SELECT * FROM posts WHERE user_id=? AND privacy = 'public' ORDER BY created_at DESC LIMIT 100"); $pp->execute([$uid]);
                }
            }
            $posts = $pp->fetchAll(PDO::FETCH_ASSOC);
            foreach ($posts as $p):
              $likes = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id=?"); $likes->execute([$p['id']]); $likeCount = $likes->fetchColumn();
              $liked = false;
              if (logged_in()) { $lk = $pdo->prepare("SELECT id FROM likes WHERE post_id=? AND user_id=? LIMIT 1"); $lk->execute([$p['id'], me_id()]); $liked = (bool)$lk->fetchColumn(); }
          ?>
            <div style="border-top:1px solid #eee;padding-top:8px;margin-top:8px">
              <div class="small"><?=esc($p['created_at'])?></div>
              <div><?=nl2br(esc($p['content']))?></div>
              <?php if($p['image']): ?><img src="uploads/<?=esc($p['image'])?>" style="max-width:100%;border-radius:8px;margin-top:8px"><?php endif; ?>
              <div style="margin-top:6px" class="small">
                <span><?=esc($likeCount)?> likes</span> ·
                <a href="#comments" class="small">comments</a>
              </div>
              <?php if(logged_in()): ?>
                <form method="post" style="display:inline" onsubmit="return ajaxLike(event,this)">
                  <input type="hidden" name="csrf" value="<?=esc($csrf)?>">
                  <input type="hidden" name="action" value="toggle_like">
                  <input type="hidden" name="post_id" value="<?=$p['id']?>">
                  <button class="small" type="submit"><?= $liked ? 'Unlike' : 'Like' ?></button>
                </form>
                <form method="post" style="display:inline;margin-left:8px">
                  <input type="hidden" name="csrf" value="<?=esc($csrf)?>">
                  <input type="hidden" name="action" value="comment">
                  <input type="hidden" name="post_id" value="<?=$p['id']?>">
                  <input class="input" name="content" placeholder="Write a comment..." style="width:auto;display:inline-block">
                  <button class="small">Comment</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </section>

        <aside class="card">
          <h3>About</h3>
          <div class="small">Profile for <?=esc($profile['username'])?></div>

          <h4 style="margin-top:12px">Incoming requests</h4>
          <?php if(!$incoming_reqs): ?><div class="small">No requests</div><?php else: foreach($incoming_reqs as $req): ?>
            <div style="margin-bottom:8px"><strong><?=esc($req['display_name'] ?? $req['username'])?></strong>
              <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="respond_friend"><input type="hidden" name="req_id" value="<?=$req['id']?>"><button class="btn" name="response" value="accept">Accept</button></form>
              <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="respond_friend"><input type="hidden" name="req_id" value="<?=$req['id']?>"><button style="background:#ddd;color:#111;border-radius:8px;padding:6px;border:0" name="response" value="reject">Reject</button></form>
            </div>
          <?php endforeach; endif; ?>

          <h4 style="margin-top:12px">People you may know</h4>
          <?php
            if (logged_in()) {
                $sugg = $pdo->prepare("SELECT id, username, display_name, avatar FROM users WHERE id != ? AND id NOT IN (
                                          SELECT CASE WHEN user_a = ? THEN user_b ELSE user_a END AS uid FROM friends WHERE user_a = ? OR user_b = ?
                                        ) ORDER BY RAND() LIMIT 6");
                $sugg->execute([me_id(), me_id(), me_id(), me_id()]); $suggest = $sugg->fetchAll(PDO::FETCH_ASSOC);
                foreach ($suggest as $s): ?>
                  <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                    <img class="avatar" src="<?= $s['avatar'] ? 'uploads/'.esc($s['avatar']) : 'https://via.placeholder.com/48' ?>">
                    <div><a href="?view=profile&uid=<?=$s['id']?>"><?=esc($s['display_name'] ?? $s['username'])?></a></div>
                    <div style="margin-left:auto"><form method="post"><input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="send_friend"><input type="hidden" name="to_user" value="<?=$s['id']?>"><button class="small">Add</button></form></div>
                  </div>
                <?php endforeach;
            } else { echo "<div class='small'>Login to see suggestions</div>"; }
          ?>
        </aside>
      </div>

    <?php elseif($view === 'notifications' && logged_in()): ?>
      <div class="card">
        <h2>Notifications</h2>
        <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="mark_notifications_seen"><button class="btn">Mark all seen</button></form>
        <div style="margin-top:12px">
          <?php if(!$notifications) echo "<div class='small'>No notifications</div>"; else foreach($notifications as $n): ?>
            <div style="padding:8px;border-bottom:1px dashed #eee">
              <div><?=esc($n['content'])?></div>
              <small><?=esc($n['created_at'])?> <?= $n['seen'] ? '' : '<strong style="color:green">• new</strong>' ?></small>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    <?php elseif($view === 'messages' && logged_in()): ?>
      <?php
        $u = (int)($_GET['u'] ?? 0);
        // list recent conversations
        $conv = $pdo->prepare("SELECT u.id,u.username,u.display_name,u.avatar, m.last_at FROM (
            SELECT from_user as uid, MAX(created_at) as last_at FROM messages WHERE to_user = :me GROUP BY from_user
            UNION
            SELECT to_user as uid, MAX(created_at) as last_at FROM messages WHERE from_user = :me GROUP BY to_user
          ) m JOIN users u ON u.id = m.uid ORDER BY m.last_at DESC LIMIT 30");
        $conv->execute([':me'=>me_id()]); $conversations = $conv->fetchAll(PDO::FETCH_ASSOC);
      ?>
      <div class="grid">
        <section class="card">
          <h3>Messages</h3>
          <div style="display:flex;gap:12px">
            <div style="width:260px">
              <?php foreach($conversations as $c): ?>
                <div style="padding:8px;border-bottom:1px dashed #eee">
                  <a href="?view=messages&u=<?=$c['id']?>"><?=esc($c['display_name'] ?? $c['username'])?></a>
                </div>
              <?php endforeach; ?>
            </div>
            <div style="flex:1">
              <?php if($u): ?>
                <?php
                  // fetch last 100 messages between me and u
                  $msgs = $pdo->prepare("SELECT m.*, fu.username as from_username, tu.username as to_username FROM messages m
                    JOIN users fu ONfu.id = m.from_user JOIN users tu ON tu.id = m.to_user
                    WHERE (from_user = :me AND to_user = :u) OR (from_user = :u AND to_user = :me) ORDER BY created_at ASC LIMIT 200");
                  // small fix: JOIN alias need correct syntax
                ?>
                <?php
                  $msgs = $pdo->prepare("SELECT m.*, fu.username as from_username, tu.username as to_username FROM messages m
                      JOIN users fu ON fu.id = m.from_user
                      JOIN users tu ON tu.id = m.to_user
                      WHERE (m.from_user = :me AND m.to_user = :u) OR (m.from_user = :u AND m.to_user = :me) ORDER BY m.created_at ASC LIMIT 200");
                  $msgs->execute([':me'=>me_id(),':u'=>$u]); $msglist = $msgs->fetchAll(PDO::FETCH_ASSOC);
                  // mark unseen to seen for messages to me
                  $pdo->prepare("UPDATE messages SET seen = 1 WHERE to_user = ? AND from_user = ?")->execute([me_id(), $u]);
                ?>
                <div style="height:420px;overflow:auto;padding:8px;border:1px solid #eee">
                  <?php foreach($msglist as $m): ?>
                    <div style="margin-bottom:8px;<?= $m['from_user']==me_id() ? 'text-align:right' : '' ?>">
                      <div style="display:inline-block;background:#f0f4ff;padding:8px;border-radius:8px;max-width:70%"><?=nl2br(esc($m['body']))?></div>
                      <div class="small"><?=esc($m['created_at'])?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <form method="post" style="margin-top:8px">
                  <input type="hidden" name="csrf" value="<?=esc($csrf)?>">
                  <input type="hidden" name="action" value="send_message">
                  <input type="hidden" name="to_user" value="<?=$u?>">
                  <input class="input" name="body" placeholder="Write a message..." required>
                  <div style="text-align:right;margin-top:6px"><button class="btn">Send</button></div>
                </form>
              <?php else: ?>
                <div class="small">Select a conversation</div>
              <?php endif; ?>
            </div>
          </div>
        </section>

        <aside class="card">
          <h4>New message</h4>
          <form method="get" action="?view=messages">
            <input class="input" name="u" placeholder="Enter user id to message">
            <div style="text-align:right;margin-top:6px"><button class="btn">Open</button></div>
          </form>
        </aside>
      </div>

    <?php elseif($view === 'groups' && logged_in()): ?>
      <?php
        $groups = $pdo->query("SELECT g.*, u.username, u.display_name FROM groups g JOIN users u ON g.owner = u.id ORDER BY g.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
      ?>
      <div class="grid">
        <section class="card">
          <h3>Groups</h3>
          <?php foreach($groups as $g): ?>
            <div style="padding:8px;border-bottom:1px dashed #eee">
              <a href="?view=group&gid=<?=$g['id']?>"><?=esc($g['name'])?></a> <div class="small">by <?=esc($g['display_name'] ?? $g['username'])?></div>
            </div>
          <?php endforeach; ?>
        </section>
        <aside class="card">
          <h4>Create group</h4>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=esc($csrf)?>">
            <input type="hidden" name="action" value="create_group">
            <input class="input" name="name" placeholder="Group name" required>
            <textarea class="input" name="description" placeholder="Description"></textarea>
            <div style="text-align:right"><button class="btn">Create</button></div>
          </form>
        </aside>
      </div>

    <?php elseif($view === 'group' && logged_in()): ?>
      <?php
        $gid = (int)($_GET['gid'] ?? 0);
        $g = $pdo->prepare("SELECT g.*, u.display_name, u.username FROM groups g JOIN users u ON g.owner = u.id WHERE g.id=? LIMIT 1"); $g->execute([$gid]); $group = $g->fetch(PDO::FETCH_ASSOC);
        if (!$group) { echo "<div class='card'>Group not found</div>"; } else {
          // check membership
          $m = $pdo->prepare("SELECT * FROM group_members WHERE group_id=? AND user_id=? LIMIT 1"); $m->execute([$gid, me_id()]); $isMember = (bool)$m->fetch();
          $gposts = $pdo->prepare("SELECT gp.*, u.display_name, u.username FROM group_posts gp JOIN users u ON gp.user_id = u.id WHERE gp.group_id = ? ORDER BY gp.created_at DESC LIMIT 200"); $gposts->execute([$gid]); $gposts = $gposts->fetchAll(PDO::FETCH_ASSOC);
      ?>
        <div class="grid">
          <section class="card">
            <h3><?=esc($group['name'])?></h3>
            <div class="small">Owner: <?=esc($group['display_name'] ?? $group['username'])?></div>
            <div class="small"><?=esc($group['description'])?></div>

            <?php if(!$isMember): ?>
              <form method="post"><input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="join_group"><input type="hidden" name="group_id" value="<?=$gid?>"><button class="btn">Join group</button></form>
            <?php else: ?>
              <h4 style="margin-top:12px">Post to group</h4>
              <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="group_post"><input type="hidden" name="group_id" value="<?=$gid?>">
                <textarea class="input" name="content" placeholder="Share something to the group..."></textarea>
                <input type="file" name="group_image" accept="image/*">
                <div style="text-align:right;margin-top:6px"><button class="btn">Post</button></div>
              </form>
            <?php endif; ?>

            <h4 style="margin-top:12px">Posts</h4>
            <?php foreach($gposts as $gp): ?>
              <div style="border-top:1px solid #eee;padding-top:8px;margin-top:8px">
                <div class="small"><?=esc($gp['display_name'] ?? $gp['username'])?> · <?=esc($gp['created_at'])?></div>
                <div><?=nl2br(esc($gp['content']))?></div>
                <?php if($gp['image']): ?><img src="uploads/<?=esc($gp['image'])?>" style="max-width:100%;border-radius:8px;margin-top:6px"><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </section>

          <aside class="card">
            <h4>Members</h4>
            <?php
              $mems = $pdo->prepare("SELECT u.* FROM users u JOIN group_members gm ON u.id = gm.user_id WHERE gm.group_id = ? ORDER BY gm.joined_at DESC LIMIT 50");
              $mems->execute([$gid]); $members = $mems->fetchAll(PDO::FETCH_ASSOC);
              foreach ($members as $mm): ?>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                  <img class="avatar" src="<?= $mm['avatar'] ? 'uploads/'.esc($mm['avatar']) : 'https://via.placeholder.com/48' ?>">
                  <div><a href="?view=profile&uid=<?=$mm['id']?>"><?=esc($mm['display_name'] ?? $mm['username'])?></a></div>
                </div>
              <?php endforeach;
            ?>
          </aside>
        </div>
      <?php } ?>

    <?php elseif($view === 'pages' && logged_in()): ?>
      <?php $pages = $pdo->query("SELECT p.*, u.display_name FROM pages p JOIN users u ON p.owner = u.id ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC); ?>
      <div class="grid">
        <section class="card">
          <h3>Pages</h3>
          <?php foreach($pages as $pg): ?>
            <div style="padding:8px;border-bottom:1px dashed #eee">
              <a href="?view=page&pid=<?=$pg['id']?>"><?=esc($pg['title'])?></a>
              <div class="small">by <?=esc($pg['display_name'])?></div>
            </div>
          <?php endforeach; ?>
        </section>
        <aside class="card">
          <h4>Create a page</h4>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="create_page">
            <input class="input" name="title" placeholder="Page title" required>
            <textarea class="input" name="description" placeholder="Description"></textarea>
            <div style="text-align:right"><button class="btn">Create</button></div>
          </form>
        </aside>
      </div>

    <?php elseif($view === 'page' && logged_in()): ?>
      <?php
        $pid = (int)($_GET['pid'] ?? 0);
        $p = $pdo->prepare("SELECT p.*, u.display_name, u.username FROM pages p JOIN users u ON p.owner = u.id WHERE p.id=? LIMIT 1"); $p->execute([$pid]); $page = $p->fetch(PDO::FETCH_ASSOC);
        if (!$page) echo "<div class='card'>Page not found</div>";
        else {
          $pposts = $pdo->prepare("SELECT pp.*, u.display_name FROM page_posts pp JOIN users u ON pp.user_id = u.id WHERE pp.page_id=? ORDER BY pp.created_at DESC"); $pposts->execute([$pid]); $pposts = $pposts->fetchAll(PDO::FETCH_ASSOC);
          $isFollower = $pdo->prepare("SELECT id FROM page_followers WHERE page_id=? AND user_id=? LIMIT 1"); $isFollower->execute([$pid, me_id()]); $isFollower=(bool)$isFollower->fetchColumn();
      ?>
        <div class="grid">
          <section class="card">
            <h3><?=esc($page['title'])?></h3>
            <div class="small"><?=esc($page['description'])?></div>
            <div class="small">Owner: <?=esc($page['display_name'] ?? $page['username'])?></div>
            <?php if(!$isFollower): ?>
              <form method="post"><input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="follow_page"><input type="hidden" name="page_id" value="<?=$pid?>"><button class="btn">Follow</button></form>
            <?php endif; ?>

            <?php if($page['owner'] == me_id()): ?>
              <h4 style="margin-top:12px">Post as page</h4>
              <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="page_post"><input type="hidden" name="page_id" value="<?=$pid?>">
                <textarea class="input" name="content" placeholder="Write post as page..."></textarea>
                <input type="file" name="page_image" accept="image/*">
                <div style="text-align:right;margin-top:6px"><button class="btn">Publish</button></div>
              </form>
            <?php endif; ?>

            <h4 style="margin-top:12px">Page posts</h4>
            <?php foreach($pposts as $ppp): ?>
              <div style="border-top:1px solid #eee;padding-top:8px;margin-top:8px">
                <div class="small"><?=esc($ppp['display_name'])?> · <?=esc($ppp['created_at'])?></div>
                <div><?=nl2br(esc($ppp['content']))?></div>
                <?php if($ppp['image']): ?><img src="uploads/<?=esc($ppp['image'])?>" style="max-width:100%;border-radius:8px;margin-top:6px"><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </section>

          <aside class="card">
            <h4>Followers</h4>
            <?php $followers = $pdo->prepare("SELECT u.* FROM users u JOIN page_followers pf ON u.id = pf.user_id WHERE pf.page_id = ? ORDER BY pf.created_at DESC LIMIT 50"); $followers->execute([$pid]); $foll = $followers->fetchAll(PDO::FETCH_ASSOC); ?>
            <?php foreach($foll as $fo): ?>
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px"><img class="avatar" src="<?= $fo['avatar'] ? 'uploads/'.esc($fo['avatar']) : 'https://via.placeholder.com/48' ?>"><div><a href="?view=profile&uid=<?=$fo['id']?>"><?=esc($fo['display_name'] ?? $fo['username'])?></a></div></div>
            <?php endforeach; ?>
          </aside>
        </div>
      <?php } ?>

    <?php elseif($view === 'events' && logged_in()): ?>
      <?php $events = $pdo->query("SELECT e.*, u.display_name FROM events e JOIN users u ON e.owner = u.id ORDER BY e.starts_at ASC")->fetchAll(PDO::FETCH_ASSOC); ?>
      <div class="grid">
        <section class="card">
          <h3>Events</h3>
          <?php foreach($events as $ev): ?>
            <div style="padding:8px;border-bottom:1px dashed #eee">
              <a href="?view=event&eid=<?=$ev['id']?>"><?=esc($ev['title'])?></a><div class="small"><?=esc($ev['starts_at'])?> · by <?=esc($ev['display_name'])?></div>
            </div>
          <?php endforeach; ?>
        </section>
        <aside class="card">
          <h4>Create event</h4>
          <form method="post">
            <input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="create_event">
            <input class="input" name="title" placeholder="Event title" required>
            <textarea class="input" name="description" placeholder="Description"></textarea>
            <input class="input" name="location" placeholder="Location">
            <label class="small">Starts at <input class="input" name="starts_at" placeholder="YYYY-MM-DD HH:MM" required></label>
            <label class="small">Ends at <input class="input" name="ends_at" placeholder="YYYY-MM-DD HH:MM"></label>
            <div style="text-align:right"><button class="btn">Create</button></div>
          </form>
        </aside>
      </div>

    <?php elseif($view === 'event' && logged_in()): ?>
      <?php $eid = (int)($_GET['eid'] ?? 0); $e = $pdo->prepare("SELECT e.*, u.display_name FROM events e JOIN users u ON e.owner = u.id WHERE e.id=? LIMIT 1"); $e->execute([$eid]); $ev = $e->fetch(PDO::FETCH_ASSOC); if (!$ev) echo "<div class='card'>Event not found</div>"; else {
            $responses = $pdo->prepare("SELECT response, COUNT(*) as cnt FROM event_responses WHERE event_id = ? GROUP BY response"); $responses->execute([$eid]); $r = $responses->fetchAll(PDO::FETCH_ASSOC);
      ?>
      <div class="card">
        <h3><?=esc($ev['title'])?></h3>
        <div class="small"><?=esc($ev['starts_at'])?> — <?=esc($ev['ends_at'])?></div>
        <div class="small"><?=esc($ev['location'])?></div>
        <div style="margin-top:8px"><?=nl2br(esc($ev['description']))?></div>
        <h4 style="margin-top:12px">Responses</h4>
        <?php foreach($r as $rr): ?><div class="small"><?=esc($rr['response'])?>: <?=esc($rr['cnt'])?></div><?php endforeach; ?>
        <form method="post" style="margin-top:8px">
          <input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="rsvp_event"><input type="hidden" name="event_id" value="<?=$eid?>">
          <select name="response" class="input"><option value="yes">Yes</option><option value="maybe">Maybe</option><option value="no">No</option></select>
          <div style="text-align:right;margin-top:6px"><button class="btn">RSVP</button></div>
        </form>
      </div>
    <?php } ?>

    <?php else: /* HOME / feed */ ?>
      <div class="grid">
        <section>
          <?php if(logged_in()): ?>
            <div class="card">
              <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="create_post">
                <textarea class="input" name="content" placeholder="What's on your mind?" rows="3"></textarea>
                <div style="display:flex;align-items:center;gap:8px;margin-top:6px">
                  <input type="file" name="image" accept="image/*">
                  <select name="privacy" class="input" style="width:140px"><option value="public">Public</option><option value="friends">Friends</option></select>
                  <div style="margin-left:auto"><button class="btn">Post</button></div>
                </div>
              </form>
            </div>
          <?php else: ?>
            <div class="card">
              <h2>Welcome to SocialDemo</h2>
              <div class="small">Register or login to start posting, connecting, and exploring groups, pages and events.</div>
            </div>
          <?php endif; ?>

          <div style="margin-top:12px">
            <?php foreach($feed_posts as $p): 
              // map type to display
              $typeLabel = $p['type'] ?? 'post';
              $likes = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?"); 
              // for group/page posts, id offset applied earlier; skip like counts (demo)
              if ($typeLabel === 'post') {
                  $likes->execute([$p['id']]); $likeCount = $likes->fetchColumn();
                  $liked = logged_in() ? (bool)$pdo->prepare("SELECT id FROM likes WHERE post_id=? AND user_id=? LIMIT 1")->execute([$p['id'], me_id()]) : false;
                  // the execute we used returns a bool and not fetch; fix below by fetching properly
                  $lk = $pdo->prepare("SELECT id FROM likes WHERE post_id=? AND user_id=? LIMIT 1"); $lk->execute([$p['id'], me_id()]); $liked = (bool)$lk->fetchColumn();
              } else {
                  $likeCount = 0; $liked=false;
              }
            ?>
              <div class="card post" style="margin-bottom:10px">
                <div style="display:flex;align-items:center;gap:10px">
                  <img class="avatar" src="<?= $p['avatar'] ? 'uploads/'.esc($p['avatar']) : 'https://via.placeholder.com/48' ?>">
                  <div>
                    <div style="font-weight:700"><?=esc($p['display_name'] ?? $p['username'])?></div>
                    <div class="small"><?=esc($p['created_at'])?> · <?=esc($typeLabel)?></div>
                  </div>
                </div>
                <div style="margin-top:8px"><?=nl2br(esc($p['content']))?></div>
                <?php if(!empty($p['image'])): ?><img src="uploads/<?=esc($p['image'])?>" style="max-width:100%;border-radius:8px;margin-top:8px"><?php endif; ?>
                <div style="margin-top:8px" class="small">
                  <?=esc($likeCount)?> likes
                  <?php if(logged_in() && $typeLabel === 'post'): ?>
                    <form method="post" style="display:inline" onsubmit="return ajaxLike(event,this)">
                      <input type="hidden" name="csrf" value="<?=esc($csrf)?>"><input type="hidden" name="action" value="toggle_like"><input type="hidden" name="post_id" value="<?=$p['id']?>">
                      <button class="small" type="submit"><?= $liked ? 'Unlike' : 'Like' ?></button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>

        <aside class="card">
          <h4>Search</h4>
          <form method="get" action="?view=search" class="searchbox">
            <input class="input" name="q" placeholder="Search users & posts">
            <div><button class="btn">Search</button></div>
          </form>

          <h4 style="margin-top:12px">Quick links</h4>
          <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px">
            <a href="?view=groups">Groups</a>
            <a href="?view=pages">Pages</a>
            <a href="?view=events">Events</a>
            <a href="?view=messages">Messages</a>
            <?php if($me && $me['role']==='admin'): ?><a href="?view=admin">Admin</a><?php endif; ?>
          </div>
        </aside>
      </div>
    <?php endif; ?>

    <div style="height:24px"></div>
    <div style="text-align:center" class="small">Demo social network — DB: <strong><?=esc(basename($dbfile))?></strong> — For learning only.</div>
  </main>

<script>
async function ajaxLike(ev, form) {
  ev.preventDefault();
  const data = new FormData(form);
  const res = await fetch(location.href, { method: 'POST', body: data });
  const text = await res.text();
  // naive: reload to update counts
  location.reload();
}
</script>
</body>
</html>
