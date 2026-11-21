"""Check exported PDF text stays on-page. Requires Poppler's pdftotext."""
import subprocess
import sys
import xml.etree.ElementTree as ET

if len(sys.argv) != 2:
    raise SystemExit('Usage: python tests/probes/pdf_bounds.py /path/to/invoice.pdf')
result = subprocess.run(['pdftotext', '-bbox', sys.argv[1], '-'], check=True, capture_output=True)
root = ET.fromstring(result.stdout)
ns = {'x': 'http://www.w3.org/1999/xhtml'}
pages = root.findall('.//x:page', ns)
assert pages, 'No PDF pages found'
words = 0
for number, page in enumerate(pages, 1):
    width, height = float(page.attrib['width']), float(page.attrib['height'])
    for word in page.findall('x:word', ns):
        words += 1
        box = {k: float(word.attrib[k]) for k in ['xMin', 'xMax', 'yMin', 'yMax']}
        assert box['xMin'] >= 0 and box['yMin'] >= 0 and box['xMax'] <= width and box['yMax'] <= height, f'Text outside page {number}: {box}'
assert words > 0, 'No text was extracted; a pixel review is required'
print(f'{len(pages)} pages checked, {words} words, no off-page text. Pixel review is still required.')
