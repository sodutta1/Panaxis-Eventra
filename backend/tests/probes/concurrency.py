import concurrent.futures, json, urllib.request, urllib.error, threading
from collections import Counter
BASE='http://127.0.0.1:8000/api/v1'
token=None

def call(method,path,data=None):
    headers={'Accept':'application/json','Content-Type':'application/json'}
    if token: headers['Authorization']='Bearer '+token
    req=urllib.request.Request(BASE+path, data=json.dumps(data).encode() if data is not None else None, headers=headers,method=method)
    try:
        with urllib.request.urlopen(req,timeout=30) as r: return r.status,json.load(r)
    except urllib.error.HTTPError as e:
        return e.code,json.load(e)

def race(n,method,path,data):
    barrier=threading.Barrier(n)
    def go(_):
        barrier.wait()
        return call(method,path,data)
    with concurrent.futures.ThreadPoolExecutor(max_workers=n) as pool:
        results=list(pool.map(go,range(n)))
    return results

def run():
    global token
    status,result=call('POST','/auth/login',{'email':'admin@eventflow.test','password':'password'})
    assert status==200,result
    token=result['token']
    event=call('GET','/events')[1]['data'][0]
    type_payload={'event_id':event['id'],'name':'Concurrency capacity probe','price':10,'total_capacity':1}
    status,tt=call('POST','/tickets/types',type_payload); assert status==201,tt
    payload={'event_id':event['id'],'ticket_type_id':tt['data']['id'],'holder_name':'Concurrent Guest','payment_status':'paid'}
    issued=race(12,'POST','/tickets',payload)
    counts=Counter(s for s,_ in issued)
    print('ticket issuance',dict(counts)); assert counts=={201:1,422:11},issued
    ticket=next(b['data'][0] for s,b in issued if s==201)
    scanned=race(12,'POST','/tickets/verify',{'token':ticket['qr_token']})
    counts=Counter(s for s,_ in scanned)
    print('gate admission',dict(counts)); assert counts=={200:1,409:11},scanned
    invoice={'client_id':event['client_id'],'event_id':event['id'],'invoice_date':'2026-11-21','due_date':'2026-12-21',
             'tax_rate':0,'items':[{'description':'Concurrency probe','quantity':1,'unit_price':100}]}
    status,inv=call('POST','/invoices',invoice); assert status==201,inv
    inv_id=inv['data']['id']
    payments=race(12,'POST',f'/invoices/{inv_id}/payments',{'amount':80,'payment_date':'2026-11-21','payment_method':'upi'})
    counts=Counter(s for s,_ in payments)
    print('invoice payments',dict(counts)); assert counts=={201:1,422:11},payments
    final=call('GET',f'/invoices/{inv_id}')[1]['data']
    assert float(final['paid_amount'])==80 and float(final['balance_due'])==20,final
    status,lead=call('POST','/leads',{'title':'Concurrent conversion','contact_name':'Concurrency Client','source':'website'})
    assert status==201,lead
    conversions=race(12,'POST',f"/leads/{lead['data']['id']}/convert",{})
    ids={b.get('client_id') for _,b in conversions}
    print('lead conversion',dict(Counter(s for s,_ in conversions)),'distinct clients',len(ids)); assert len(ids)==1 and None not in ids,conversions
    vendor=call('GET','/vendors')[1]['data'][0]
    status,wo=call('POST',f"/events/{event['id']}/work-orders",{'vendor_id':vendor['id'],'service_title':'Race probe','contract_amount':100})
    assert status==201,wo
    wp=race(12,'POST',f"/work-orders/{wo['data']['id']}/payment",{'amount':10})
    orders=call('GET',f"/events/{event['id']}/work-orders")[1]['data']
    result=next(o for o in orders if o['id']==wo['data']['id'])
    print('work order payments',dict(Counter(s for s,_ in wp)),'paid',result['paid_amount']); assert float(result['paid_amount'])==100,result
    budget_path=f"/events/{event['id']}/budget"
    before_budget=call('GET',budget_path)[1]['data']
    additions=race(12,'POST',budget_path+'/items',{'category':'Concurrency','item_name':'Atomic budget probe','estimated_cost':10})
    after_budget=call('GET',budget_path)[1]['data']
    assert all(s==201 for s,_ in additions),additions
    assert round(float(after_budget['total_estimated_cost'])-float(before_budget['total_estimated_cost']),2)==120,after_budget
    print('budget additions',dict(Counter(s for s,_ in additions)),'total increment',120)
    print('ALL CONCURRENCY CHECKS PASSED')

if __name__=='__main__': run()
