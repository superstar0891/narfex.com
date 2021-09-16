const timezones = {
  "0": "Europe/London",
  "1": "Africa/Lagos",
  "2": "Europe/Zurich",
  "3": "Europe/Vilnius",
  "4": "Asia/Muscat",
  "6": "Asia/Almaty",
  "7": "Asia/Jakarta",
  "8": "Asia/Singapore",
  "9": "Asia/Tokyo",
  "10": "Australia/Sydney",
  "12": "Pacific/Auckland",
  "13": "Pacific/Fakaofo",
  "-5": "America/Mexico_City",
  "-4.5": "America/Caracas",
  "-6": "America/El_Salvador",
  "-9": "America/Juneau",
  "-8": "America/Los_Angeles",
  "-7": "America/Vancouver",
  "-4": "America/Toronto",
  "-3": "America/Sao_Paulo",
  "5.75": "Asia/Kathmandu",
  "5.5": "Asia/Kolkata",
  "4.5": "Asia/Tehran",
  "9.5": "Australia/Adelaide",
  "-10": "Pacific/Honolulu"
};

export default () => {
  return timezones[((new Date().getTimezoneOffset() / 60) * -1).toString()];
};
