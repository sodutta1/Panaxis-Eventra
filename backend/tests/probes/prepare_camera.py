import importlib.util,json,subprocess
from pathlib import Path
from PIL import Image
import qrcode
spec=importlib.util.spec_from_file_location('probe',str(Path(__file__).with_name('concurrency.py')))
m=importlib.util.module_from_spec(spec); spec.loader.exec_module(m)
status,login=m.call('POST','/auth/login',{'email':'admin@eventflow.test','password':'password'})
assert status==200
m.token=login['token']
event=m.call('GET','/events')[1]['data'][0]
status,tt=m.call('POST','/tickets/types',{'event_id':event['id'],'name':'Camera regression','price':10,'total_capacity':10}); assert status==201
status,t=m.call('POST','/tickets',{'event_id':event['id'],'ticket_type_id':tt['data']['id'],'holder_name':'Camera Regression Guest','payment_status':'paid'}); assert status==201
ticket=t['data'][0]
Path('/tmp/eventra-camera-session.json').write_text(json.dumps({'token':login['token'],'user':login['user'],'qr':ticket['qr_token']}))
qr=qrcode.QRCode(box_size=5,border=4); qr.add_data(ticket['qr_token']); qr.make(fit=True)
im=qr.make_image().convert('RGB').resize((210,210),Image.Resampling.NEAREST)
scene=Image.new('RGB',(640,480),'white'); scene.paste(im,((640-210)//2,(480-210)//2)); scene.save('/tmp/eventra-qr.png')
subprocess.run(['ffmpeg','-y','-loglevel','error','-loop','1','-i','/tmp/eventra-qr.png','-t','6','-r','10','-pix_fmt','yuv420p','/tmp/eventra-qr.y4m'],check=True)
print('Prepared a fresh ticket and synthetic QR camera feed')
