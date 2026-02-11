#![cfg_attr(
    all(not(debug_assertions), target_os = "windows"),
    windows_subsystem = "windows"
)]

use std::fs;
use std::process::Command;

/// Daftar printer yang terdeteksi di sistem (macOS: lpstat, Windows: PowerShell, Linux: lpstat)
#[tauri::command]
fn get_system_printers() -> Result<Vec<String>, String> {
    #[cfg(target_os = "windows")]
    {
        let output = Command::new("powershell")
            .args([
                "-NoProfile",
                "-Command",
                "Get-Printer | Select-Object -ExpandProperty Name",
            ])
            .output()
            .map_err(|e| e.to_string())?;
        if !output.status.success() {
            let stderr = String::from_utf8_lossy(&output.stderr);
            return Err(format!("PowerShell error: {}", stderr));
        }
        let stdout = String::from_utf8_lossy(&output.stdout);
        let printers: Vec<String> = stdout
            .lines()
            .map(|s| s.trim().to_string())
            .filter(|s| !s.is_empty())
            .collect();
        return Ok(printers);
    }

    #[cfg(any(target_os = "macos", target_os = "linux"))]
    {
        let output = Command::new("lpstat")
            .arg("-p")
            .output()
            .map_err(|e| e.to_string())?;
        if !output.status.success() {
            let stderr = String::from_utf8_lossy(&output.stderr);
            return Err(format!("lpstat error: {}", stderr));
        }
        let stdout = String::from_utf8_lossy(&output.stdout);
        // Format: "printer PrinterName is idle."
        let printers: Vec<String> = stdout
            .lines()
            .filter_map(|line| {
                let line = line.trim();
                if line.starts_with("printer ") {
                    let rest = line.trim_start_matches("printer ");
                    let name = rest.split_whitespace().next()?;
                    Some(name.to_string())
                } else {
                    None
                }
            })
            .collect();
        Ok(printers)
    }
}

/// Cetak teks struk ke printer. Jika printer_name kosong, pakai printer default sistem (menghindari window.print() di WebView macOS yang bermasalah).
#[tauri::command]
fn print_receipt_to_printer(printer_name: String, content: String) -> Result<(), String> {
    let printer_name = printer_name.trim();
    let temp_dir = std::env::temp_dir();
    let path = temp_dir.join("pos_receipt.txt");
    fs::write(&path, content.as_bytes()).map_err(|e| e.to_string())?;

    #[cfg(target_os = "windows")]
    {
        let path_str = path.to_string_lossy();
        let cmd = if printer_name.is_empty() {
            format!(
                "Get-Content -Path '{}' -Encoding UTF8 | Out-Printer",
                path_str.replace('\'', "''")
            )
        } else {
            format!(
                "Get-Content -Path '{}' -Encoding UTF8 | Out-Printer -Name '{}'",
                path_str.replace('\'', "''"),
                printer_name.replace('\'', "''")
            )
        };
        let output = Command::new("powershell")
            .args(["-NoProfile", "-Command", &cmd])
            .output()
            .map_err(|e| e.to_string())?;
        let _ = fs::remove_file(&path);
        if !output.status.success() {
            let stderr = String::from_utf8_lossy(&output.stderr);
            return Err(format!("Gagal cetak: {}", stderr));
        }
        Ok(())
    }

    #[cfg(any(target_os = "macos", target_os = "linux"))]
    {
        let path_str = path.to_string_lossy();
        let output = if printer_name.is_empty() {
            Command::new("lp").arg(path_str.as_ref()).output()
        } else {
            Command::new("lp")
                .args(["-d", printer_name, path_str.as_ref()])
                .output()
        };
        let output = output.map_err(|e| e.to_string())?;
        let _ = fs::remove_file(&path);
        if !output.status.success() {
            let stderr = String::from_utf8_lossy(&output.stderr);
            return Err(format!("Gagal cetak: {}", stderr));
        }
        Ok(())
    }
}

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .plugin(tauri_plugin_shell::init())
        .invoke_handler(tauri::generate_handler![get_system_printers, print_receipt_to_printer])
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
