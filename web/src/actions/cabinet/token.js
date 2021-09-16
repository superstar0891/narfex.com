import * as actionTypes from "../actionTypes";

export function tokenInit() {
  return {
    type: actionTypes.TOKEN_INIT
  };
}

export function tokenSet(payload) {
  return {
    type: actionTypes.TOKEN_SET,
    payload
  };
}

export function tokenSetCurrency(currency) {
  return {
    type: actionTypes.TOKEN_SET_CURRENCY,
    payload: currency
  };
}

export function tokenSetAmount(amount) {
  return {
    type: actionTypes.TOKEN_SET_AMOUNT,
    payload: amount
  };
}

export function tokenSetPromoCode(code) {
  return {
    type: actionTypes.TOKEN_SET_PROMOCODE,
    payload: code
  };
}
export function tokenBuy() {
  return {
    type: actionTypes.TOKEN_BUY
  };
}

export function tokenSetStatus(type, value) {
  return {
    type: actionTypes.TOKEN_SET_STATUS,
    payload: {
      type,
      value
    }
  };
}
