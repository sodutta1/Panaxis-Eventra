export const EVENT_TIME_ZONE = 'Asia/Kolkata'

export function formatEventDateTime(value: string): string {
  return new Date(value).toLocaleString('en-IN', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: EVENT_TIME_ZONE,
  })
}

export function formatDate(value?:string):string {
 if(!value)return 'Not set'
 const date=new Date(value.length===10?value+'T12:00:00+05:30':value)
 return Number.isNaN(date.getTime())?'Not set':date.toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric',timeZone:EVENT_TIME_ZONE})
}
