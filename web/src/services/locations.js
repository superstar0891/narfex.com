export function isIndonesia() {
  return (
    navigator.language === "id" ||
    Intl.DateTimeFormat()
      .resolvedOptions()
      .timeZone.split("/")[0]
      .toLowerCase() === "asia"
  );
}
