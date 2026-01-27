from PIL import Image
import os

img_path = r'E:\Websites\doctor.niufin.cloud\native_windows_app\icon.png'
save_path = r'E:\Websites\doctor.niufin.cloud\wpf_app\NiufinDoctorWPF\icon.ico'

img = Image.open(img_path)
img.save(save_path, format='ICO', sizes=[(256, 256), (128, 128), (64, 64), (48, 48), (32, 32), (16, 16)])
print("Icon created successfully.")
