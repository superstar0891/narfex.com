import * as storage from "./storage";

const savedParams = ["ref", "i"];

const initGetParams = (location => {
  const params = {},
    storageParams = {},
    search = location.search.slice(1).split("&");

  if (search[0] !== "") {
    search.forEach(param => {
      const [key, value] = param.split("=");
      params[key] = value;

      if (savedParams.includes(key)) {
        storage.setItem(key, value);
      }
    });
  }

  savedParams.forEach(key => {
    const value = storage.getItem(key);
    if (value) {
      storageParams[key] = value;
    }
  });

  return {
    params: {
      ...storageParams,
      ...params
    },
    hash: location.hash.slice(1)
  };
})(window.location);

export default initGetParams;
