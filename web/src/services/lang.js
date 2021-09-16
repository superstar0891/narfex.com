import * as storage from "./storage";
import moment from "moment";
import { loadLang } from "../actions";

export function choseLang(lang) {
  const momentLang = lang === "ru" ? "ru" : "en-au";
  require("moment/locale/" + momentLang);
  moment.locale(momentLang);

  if (lang) {
    storage.setItem("lang", lang);
    moment.locale(lang);
  }
}

export function setLang(lang, callback) {
  choseLang(lang);

  if (lang) {
    loadLang(lang).then(e => {
      if (callback) callback();
    });
  }
}

export function getLang() {
  return (
    storage.getItem("lang") ||
    (navigator.language || navigator.userLanguage).split("-")[0]
  );
}
