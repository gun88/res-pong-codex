export class Common {

  public static duration(start: string, end: string): string {
    const startDate = new Date(start);
    const endDate = new Date(end);

    const diffMs = endDate.getTime() - startDate.getTime();
    const diffMinutes = Math.floor(diffMs / 60000);
    const hours = Math.floor(diffMinutes / 60);
    const minutes = diffMinutes % 60;

    const durationParts: string[] = [];
    if (hours > 0) durationParts.push(`${hours}h`);
    if (minutes > 0) durationParts.push(`${minutes}m`);


    return durationParts.join(" e ");
  }


  public static getMonthStartEnd(index: number, baseYear: number = 1970, firstDayOfWeek: number = 1): {
    start: string;
    end: string
  } {

    const year = baseYear + Math.floor(index / 12);
    const month = index % 12;

    const firstOfMonth = new Date(year, month, 1);
    const lastOfMonth = new Date(year, month + 1, 0);

    // JS: getDay() -> 0=Dom ... 6=Sab
    const firstDay = firstOfMonth.getDay();
    const startOffset = (firstDay - firstDayOfWeek + 7) % 7;
    const startDate = new Date(year, month, 1 - startOffset);

    const endDayOfWeek = (firstDayOfWeek + 6) % 7; // ultimo giorno della settimana
    const lastDay = lastOfMonth.getDay();
    const endOffset = (endDayOfWeek - lastDay + 7) % 7;
    const endDate = new Date(year, month, lastOfMonth.getDate() + endOffset);

    // Normalizza orario
    startDate.setHours(0, 0, 0, 0);
    endDate.setHours(23, 59, 59, 0);

    const format = (d: Date) =>
      `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(
        d.getDate()
      ).padStart(2, '0')} ${String(d.getHours()).padStart(2, '0')}:${String(
        d.getMinutes()
      ).padStart(2, '0')}:${String(d.getSeconds()).padStart(2, '0')}`;

    return {start: format(startDate), end: format(endDate)};
  }

  public static getMonthIndexFromDate(date: Date = new Date(), baseYear: number = 1970): number {
    return (date.getFullYear() - baseYear) * 12 + date.getMonth();
  }

  public static formatMonthYear(index: number, baseYear: number = 1970): string {
    const year = baseYear + Math.floor(index / 12);
    const month = index % 12;
    const date = new Date(year, month, 1);

    return date.toLocaleString('it-IT', {month: 'long', year: 'numeric'});
  }

  public static getMonthYearFromIndex(index: number, baseYear: number = 1970): { month: number, year: number } {
    const year = baseYear + Math.floor(index / 12);
    const month = (index % 12);
    return {month, year};
  }

  public static getFirstDayOfTheMonth(index: number) {
    let {month, year} = Common.getMonthYearFromIndex(index);

    return new Date(year, month, 1);
  }


  public static extractTime(datetime: any) {
    if (!datetime) return '';
    // datetime: "YYYY-MM-DD HH:MM:SS"
    const parts = datetime.split(' ');
    if (parts.length < 2) return '';
    const timeParts = parts[1].split(':');
    if (timeParts.length < 2) return '';
    return `${timeParts[0]}:${timeParts[1]}`;
  }

  public static getDatePrefix(index: number, baseYear: number = 1970): string {
    const year = baseYear + Math.floor(index / 12);
    const month = (index % 12) + 1; // +1 per avere 1..12
    return `${year}-${month.toString().padStart(2, '0')}`;
  }

  static fixNumber(event: any, key: string) {
    try {
      event[key] = event[key] ? Number(event[key]) : event[key];
    } catch (e) {
      console.warn('fixNumber: ' + e);
    }

  }

  public static getDaysOfMonth(index: number, baseYear: number = 1970): {
    dayIndex: number; // 0 = lunedì, 6 = domenica
    day: number;      // numero del giorno (1..31)
    date: string;     // formato YYYY-MM-DD HH:mm:ss
  }[] {
    const year = baseYear + Math.floor(index / 12);
    const month = index % 12;

    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const days: { dayIndex: number; day: number; date: string }[] = [];

    const format = (d: Date) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

    for (let d = 1; d <= daysInMonth; d++) {
      const dateObj = new Date(year, month, d, 0, 0, 0);
      const jsDay = dateObj.getDay(); // 0 = domenica, 1 = lunedì, ...
      const dayIndex = (jsDay + 6) % 7; // 0 = lunedì ... 6 = domenica

      days.push({
        day: d,
        dayIndex,
        date: format(dateObj)
      });
    }

    return days;
  }

}
