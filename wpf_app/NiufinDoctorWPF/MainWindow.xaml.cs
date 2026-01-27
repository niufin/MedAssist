using System;
using System.IO;
using System.Windows;
using Microsoft.Web.WebView2.Core;

namespace NiufinDoctorWPF;

/// <summary>
/// Interaction logic for MainWindow.xaml
/// </summary>
public partial class MainWindow : Window
{
    public MainWindow()
    {
        InitializeComponent();
        InitializeWebView();
    }

    private async void InitializeWebView()
    {
        try
        {
            // Set User Data Folder to AppData/Local/NiufinDoctor to avoid permission issues in Program Files
            string userDataFolder = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "NiufinDoctor");
            
            var env = await CoreWebView2Environment.CreateAsync(null, userDataFolder);
            
            await webView.EnsureCoreWebView2Async(env);
            
            webView.Source = new Uri("https://doctor.niufin.cloud");
        }
        catch (Exception ex)
        {
            MessageBox.Show($"Failed to initialize WebView2: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }
}
