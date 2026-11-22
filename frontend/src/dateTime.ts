export const EVENT_TIME_ZONE = 'Asia/Kolkata'

export function formatEventDateTime(value: string): string {
  return new Date(value).toLocaleString('en-IN', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: EVENT_TIME_ZONE,
  })
}
