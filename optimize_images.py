#!/usr/bin/env python3
"""
Image optimization script for Portfolio
Generates optimized JPEG, WebP, and responsive variants
"""

import os
from pathlib import Path

try:
    from PIL import Image
except ImportError:
    print("PIL not found. Install with: pip install Pillow")
    exit(1)

def ensure_dir(path):
    """Create directory if it doesn't exist"""
    Path(path).mkdir(parents=True, exist_ok=True)

def optimize_image(source_path, output_dir):
    """
    Optimize images: create JPEG, WebP, and responsive variants
    """
    ensure_dir(output_dir)
    
    # Open original image
    try:
        img = Image.open(source_path)
        print(f"✓ Opened image: {source_path}")
        print(f"  Original size: {img.size}")
    except Exception as e:
        print(f"✗ Error opening image: {e}")
        return False
    
    # Convert to RGB if needed (for JPEG compatibility)
    if img.mode in ('RGBA', 'LA', 'P'):
        img = img.convert('RGB')
    
    basename = Path(source_path).stem
    
    # 1. Optimized JPEG (full-width, full quality)
    jpeg_path = os.path.join(output_dir, f"{basename}.jpg")
    img.save(jpeg_path, "JPEG", quality=85, optimize=True)
    size_kb = os.path.getsize(jpeg_path) / 1024
    print(f"✓ JPEG optimized: {basename}.jpg ({size_kb:.1f} KB)")
    
    # 2. WebP full-width
    webp_path = os.path.join(output_dir, f"{basename}.webp")
    img.save(webp_path, "WEBP", quality=80)
    size_kb = os.path.getsize(webp_path) / 1024
    print(f"✓ WebP full: {basename}.webp ({size_kb:.1f} KB)")
    
    # 3. Desktop variant (aspect ratio 16:9, crop from left)
    # Keep the image wider for desktop (show more of the right side)
    width, height = img.size
    desktop_height = height
    desktop_width = int(desktop_height * 16 / 9)
    
    # Crop from right side (left=0) to show the right part
    if desktop_width < width:
        img_desktop = img.crop((0, 0, desktop_width, desktop_height))
    else:
        img_desktop = img
    
    desktop_jpg = os.path.join(output_dir, f"{basename}-desktop.jpg")
    img_desktop.save(desktop_jpg, "JPEG", quality=85, optimize=True)
    size_kb = os.path.getsize(desktop_jpg) / 1024
    print(f"✓ Desktop JPEG: {basename}-desktop.jpg ({size_kb:.1f} KB)")
    
    desktop_webp = os.path.join(output_dir, f"{basename}-desktop.webp")
    img_desktop.save(desktop_webp, "WEBP", quality=80)
    size_kb = os.path.getsize(desktop_webp) / 1024
    print(f"✓ Desktop WebP: {basename}-desktop.webp ({size_kb:.1f} KB)")
    
    # 4. Mobile variant (aspect ratio 4:3, crop from center)
    mobile_height = height
    mobile_width = int(mobile_height * 4 / 3)
    
    # Crop from center horizontally
    if mobile_width < width:
        left = (width - mobile_width) // 2
        img_mobile = img.crop((left, 0, left + mobile_width, mobile_height))
    else:
        img_mobile = img
    
    mobile_jpg = os.path.join(output_dir, f"{basename}-mobile.jpg")
    img_mobile.save(mobile_jpg, "JPEG", quality=85, optimize=True)
    size_kb = os.path.getsize(mobile_jpg) / 1024
    print(f"✓ Mobile JPEG: {basename}-mobile.jpg ({size_kb:.1f} KB)")
    
    mobile_webp = os.path.join(output_dir, f"{basename}-mobile.webp")
    img_mobile.save(mobile_webp, "WEBP", quality=80)
    size_kb = os.path.getsize(mobile_webp) / 1024
    print(f"✓ Mobile WebP: {basename}-mobile.webp ({size_kb:.1f} KB)")
    
    print("\n✓ All images optimized successfully!")
    return True

if __name__ == "__main__":
    script_dir = Path(__file__).parent
    source_image = script_dir / "assets" / "images" / "profil.jpeg"
    output_dir = script_dir / "assets" / "images"
    
    if not source_image.exists():
        print(f"✗ Source image not found: {source_image}")
        exit(1)
    
    print("Image Optimization Tool")
    print("=" * 50)
    optimize_image(str(source_image), str(output_dir))
