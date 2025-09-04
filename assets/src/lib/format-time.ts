export function formatTime(time: number) {
  let timestamp = '';
  const hours = Math.floor(time / 3600);
  if (hours > 0) {
    timestamp += `${hours}:`;
  }
  const minutes = Math.floor(time / 60) % 60;
  timestamp += `${minutes < 10 ? "0" + minutes : minutes}:`;
  const seconds = Math.floor(time % 60);
  timestamp += `${seconds < 10 ? "0" + seconds : seconds}`;
  return timestamp;
}
