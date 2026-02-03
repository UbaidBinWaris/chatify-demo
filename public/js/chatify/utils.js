/**
 * changes date string to time ago string (WhatsApp-style).
 * @param dateString - The date string to convert to a time ago string.
 * @returns A string that tells the user how long ago the date was.
 */
function dateStringToTimeAgo(dateString) {
  const now = new Date();
  const date = new Date(dateString);
  const seconds = Math.floor((now - date) / 1000);
  const minutes = Math.floor(seconds / 60);
  const hours = Math.floor(minutes / 60);
  const days = Math.floor(hours / 24);
  
  // Format time as "10:24 pm"
  const formatTime = (d) => {
    let hours = d.getHours();
    const minutes = d.getMinutes();
    const ampm = hours >= 12 ? 'pm' : 'am';
    hours = hours % 12;
    hours = hours ? hours : 12;
    const minutesStr = minutes < 10 ? '0' + minutes : minutes;
    return `${hours}:${minutesStr} ${ampm}`;
  };
  
  // Get day name
  const getDayName = (d) => {
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return days[d.getDay()];
  };
  
  // Format date as "Jan 15"
  const formatDate = (d) => {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${months[d.getMonth()]} ${d.getDate()}`;
  };
  
  // Check if dates are on the same day
  const isSameDay = (d1, d2) => {
    return d1.getDate() === d2.getDate() &&
           d1.getMonth() === d2.getMonth() &&
           d1.getFullYear() === d2.getFullYear();
  };
  
  // Check if date is yesterday
  const isYesterday = (d) => {
    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);
    return isSameDay(d, yesterday);
  };
  
  // Less than 30 seconds
  if (seconds < 30) {
    return "just now";
  }
  // Less than 1 minute
  else if (seconds < 60) {
    return `${seconds} sec ago`;
  }
  // Less than 1 hour
  else if (minutes < 60) {
    return `${minutes} min ago`;
  }
  // Today but more than 1 hour ago - show time
  else if (isSameDay(date, now)) {
    return formatTime(date);
  }
  // Yesterday
  else if (isYesterday(date)) {
    return `Yesterday`;
  }
  // Within last 7 days - show day name
  else if (days < 7) {
    return getDayName(date);
  }
  // More than a week - show date
  else {
    return formatDate(date);
  }
}
/**
 * It returns a function that, when invoked, will wait for a specified amount of time before executing
 * the original function.
 * @param callback - The function to be executed after the delay.
 * @param delay - The amount of time to wait before calling the callback.
 * @returns A function that will call the callback function after a delay.
 */
function debounce(callback, delay) {
  let timerId;
  return function (...args) {
    clearTimeout(timerId);
    timerId = setTimeout(() => {
      callback.apply(this, args);
    }, delay);
  };
}
