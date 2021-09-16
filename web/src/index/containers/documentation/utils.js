export const sortDocSchema = schema => (a, b) => {
  const [c, d] = [a, b].map(i => schema[i]);
  if (c.name !== "Default" && d.name === "Default") {
    return -1;
  }
  if (!c.name && !d.name) {
    return a > b ? 1 : -1;
  }
  if (d.name && !c.name) {
    return 1;
  } else if (!d.name && c.name) {
    return -1;
  }
  if (c.name > d.name) {
    return 1;
  }
  if (c.name < d.name) {
    return -1;
  }
  return 0;
};
