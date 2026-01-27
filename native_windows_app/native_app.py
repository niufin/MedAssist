import webview
import sys
import os

def main():
    # Icon path handling for PyInstaller
    if getattr(sys, 'frozen', False):
        application_path = sys._MEIPASS
    else:
        application_path = os.path.dirname(os.path.abspath(__file__))

    icon_path = os.path.join(application_path, 'icon.png')
    
    # Use icon if it exists
    if not os.path.exists(icon_path):
        icon_path = None

    window = webview.create_window(
        'Niufin Doctor', 
        'https://doctor.niufin.cloud', 
        width=1280, 
        height=800, 
        resizable=True,
        min_size=(800, 600)
    )
    
    # Start the application
    # debug=True allows inspecting elements with F12, helpful for now. 
    # Can change to False for release.
    webview.start(debug=False)

if __name__ == '__main__':
    main()
