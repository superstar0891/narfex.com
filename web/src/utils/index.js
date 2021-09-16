//styles
// external
import React, { useEffect, useRef } from "react";
import bn from "big.js";
// internal
import store from "../store";
import router from "../router";
import moment from "moment";
import * as api from "src/services/api";
import TranslatorMode from "src/index/components/cabinet/TranslatorMode/TranslatorModal";
import { userRole } from "../actions/cabinet/profile";
import REGEXES from "src/index/constants/regexes";

export function classNames() {
  let result = [];

  [].concat(Array.prototype.slice.call(arguments)).forEach(function(item) {
    if (!item) {
      return;
    }
    switch (typeof item === "undefined" ? "undefined" : typeof item) {
      case "string":
        result.push(item);
        break;
      case "object":
        Object.keys(item).forEach(function(key) {
          if (item[key]) {
            result.push(key);
          }
        });
        break;
      default:
        result.push("" + item);
    }
  });

  return result.join(" ");
}

export function removeProperty(object, ...properties) {
  let newObject = Object.assign({}, object);
  for (let property of properties) {
    delete newObject[property];
  }
  return newObject;
}

export function joinComponents(separator = ", ") {
  return (accu, elem) => {
    return accu === null ? [elem] : [...accu, separator, elem];
  };
}

export function getLang(key, string = false, code = false) {
  const state = store.getState();
  const { currentLang, translations } = state.default;
  let langString = translations[code || currentLang][key] || key;

  if (["object", "string"].includes(typeof string) || !string) {
    if (
      state.default.profile.user &&
      userRole("translator") &&
      state.settings &&
      state.settings.translator
    ) {
      return (
        <TranslatorMode
          langContent={string !== false ? string : nl2br(langString)}
          langKey={key}
        />
      );
    }
    return ["object", "string"].includes(typeof string)
      ? string
      : nl2br(langString);
  }

  return langString;
}

export const getCssVar = (v, fallback = "#AAA") => {
  return (
    (window.getComputedStyle &&
      window
        .getComputedStyle(document.body)
        .getPropertyValue(v)
        .trim()) ||
    fallback
  );
};

export const nl2br = text => {
  if (text && text.includes("\\n")) {
    return text.split("\\n").map((item, i) => (
      <>
        {item}
        <br />
      </>
    ));
  }
  return text;
};

export function isJson(string) {
  try {
    return JSON.parse(string);
  } catch (e) {
    return false;
  }
}
export const isEmail = email => REGEXES.email.test(email.toLowerCase());

export const isName = name => REGEXES.name.test((name || "").toLowerCase());

export const isLogin = name => REGEXES.login.test((name || "").toLowerCase());

export const isPassword = password =>
  Object.values(REGEXES.createPassword).every(r => {
    return r.test(password);
  });

export function useInterval(callback, delay) {
  const savedCallback = useRef();

  // Remember the latest callback.
  useEffect(() => {
    savedCallback.current = callback;
  }, [callback]);

  // Set up the interval.
  useEffect(() => {
    function tick() {
      savedCallback.current();
    }
    if (delay !== null) {
      let id = setInterval(tick, delay);
      return () => clearInterval(id);
    }
  }, [delay]);
}

export function diff(a1, a2) {
  return a1
    .filter(i => !a2.includes(i))
    .concat(a2.filter(i => !a1.includes(i)));
}

export const formatNumber = (
  num,
  minimumFractionDigits = 2,
  maximumFractionDigits = 2
) => {
  if (num) {
    return num.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  return null;
};

export function isProduction() {
  return (
    !api.API_ENTRY.includes("stage") &&
    !api.API_ENTRY.includes("api-") &&
    !api.API_ENTRY.includes("127.0.0.1")
  );
}

export function throttle(func, ms) {
  let timeout = null;

  return (...args) => {
    if (timeout) {
      clearTimeout(timeout);
    }
    timeout = setTimeout(func.bind(this, ...args), ms);
  };
}

export function throttle2(func, ms) {
  let isThrottled = false,
    savedArgs,
    savedThis;

  function wrapper() {
    if (isThrottled) {
      // (2)
      savedArgs = arguments;
      savedThis = this;
      return;
    }

    func.apply(this, arguments); // (1)

    isThrottled = true;

    setTimeout(function() {
      isThrottled = false; // (3)
      if (savedArgs) {
        wrapper.apply(savedThis, savedArgs);
        savedArgs = savedThis = null;
      }
    }, ms);
  }

  return wrapper;
}

export function debounce(func, ms) {
  let isCooldown = false;
  return function() {
    if (isCooldown) return;
    func.apply(this, arguments);
    isCooldown = true;
    setTimeout(() => (isCooldown = false), ms);
  };
}

export function ucfirst(input = "") {
  if (typeof input !== "string") return "";
  return input.charAt(0).toUpperCase() + input.slice(1);
}

export function formatDouble(input, fractionDigits = 8) {
  if (isNaN(parseFloat(input)) || Math.abs(input) === Infinity) return null;
  const coefficient = parseInt(1 + "0".repeat(fractionDigits));
  return (
    Math.floor(
      bn(input)
        .mul(coefficient)
        .toExponential()
    ) / coefficient
  );
  // return parseFloat(parseFloat(input).toFixed(fractionDigits));
}

export function formatTableId(index) {
  const lenght = `${index}`.length;
  const minLenght = 3;
  const need = minLenght - lenght;

  if (need <= 0) {
    return index;
  }

  let arr = new Array(need).fill(0);
  arr.push(index);
  return arr.join("");
}

export function makeModalParams(modal, params) {
  let result = Object.assign({}, router.getState().params);
  return {
    ...result,
    modal,
    ...params
  };
}

export function InputNumberOnKeyPressHandler(e) {
  if (isNaN(parseInt(e.key))) {
    return e.preventDefault();
  }
}

export function __doubleInputOnKeyPressHandler(e, value = "") {
  switch (e.key) {
    default:
      if (isNaN(parseInt(e.key)) || (value.length === 1 && value[0] === "0")) {
        e.preventDefault();
      }
      break;
    case ".": {
      return value.length === 0
        ? e.preventDefault()
        : value.indexOf(e.key) > -1 && e.preventDefault();
    }
  }
}

export function clipTextMiddle(text = "", length = 10) {
  if (typeof text !== "string") return "";

  if (text.length <= length + length / 2) {
    return text;
  }

  let parts = [text.substr(0, length), "...", text.substr(-length / 2)];
  return parts.join("");
}

export function switchMatch(key, node) {
  const __DEFAULT__ = "default";
  switch (typeof node) {
    case "object": {
      switch (typeof key) {
        case "boolean":
          return node[key];
        default:
        case "string": {
          if (node.hasOwnProperty(key)) {
            return node[key];
          } else {
            if (node.hasOwnProperty(__DEFAULT__)) {
              switch (typeof node[__DEFAULT__]) {
                case "function": {
                  return node[__DEFAULT__]();
                }
                default:
                  return node[__DEFAULT__];
              }
            } else {
              return key;
            }
          }
        }
      }
    }
    default:
      break;
  }
}

export function getScrollbarWidth() {
  const outer = document.createElement("div");
  outer.style.visibility = "hidden";
  outer.style.width = "100px";
  document.body.appendChild(outer);
  const widthNoScroll = outer.offsetWidth;
  outer.style.overflow = "scroll";
  const inner = document.createElement("div");
  inner.style.width = "100%";
  outer.appendChild(inner);
  const widthWithScroll = inner.offsetWidth;
  outer.parentNode.removeChild(outer);
  return widthNoScroll - widthWithScroll;
}

export function isFiat(currency = "") {
  return ["gbp", "usd", "usdt", "eur", "rub", "idr", "cny"].includes(
    currency.toLowerCase()
  );
  // TODO: Брать из state.default.currency
}

export function dateFormat(date, format = "DD MMMM YYYY, HH:mm") {
  let dateObject;

  if (typeof date === "number" && date.toString().length === 10) {
    dateObject = moment.unix(date);
  } else {
    const offsetMoscow = 60 * 3;
    const offset = new Date().getTimezoneOffset() + offsetMoscow;
    dateObject = moment(date).subtract(offset, "minutes");
  }

  return !!format ? dateObject.format(format) : dateObject;
}
