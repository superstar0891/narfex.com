import * as actionTypes from "../actionTypes";

export function fetchWalletPage() {
  return { type: actionTypes.WALLET_FETCH_PAGE };
}

export function walletSetInitState(payload) {
  return { type: actionTypes.WALLET_SET_INIT_STATE, payload };
}

export function walletSetStatus(section, status) {
  return {
    type: actionTypes.WALLET_SET_STATUS,
    section,
    status
  };
}

export function walletHistorySet(payload) {
  return {
    type: actionTypes.WALLET_HISTORY_SET,
    payload
  };
}

export function walletHistoryAddMore(payload) {
  return {
    type: actionTypes.WALLET_HISTORY_ADD_MORE,
    payload
  };
}

export function walletSetSaving(id) {
  return {
    type: actionTypes.WALLET_SET_SAVING,
    payload: id
  };
}
export function walletEnableSaving(id) {
  return {
    type: actionTypes.WALLET_ENABLE_SAVING,
    payload: id
  };
}

export function walletFetchHistory(payload) {
  return { type: actionTypes.WALLET_FETCH_HISTORY, payload };
}

export function walletFetchHistoryMore(payload) {
  return { type: actionTypes.WALLET_FETCH_HISTORY_MORE, payload };
}

export function walletSwapSetRate(payload) {
  return { type: actionTypes.WALLET_SWAP_SET_RATE, payload };
}

export function walletSwapSetAmount(type, value) {
  return { type: actionTypes.WALLET_SWAP_SET_AMOUNT, payload: { type, value } };
}

export function walletSwapUpdateAmount(type, value) {
  return {
    type: actionTypes.WALLET_SWAP_UPDATE_AMOUNT,
    payload: { type, value }
  };
}

export function walletSwapSetCurrency(type, value) {
  return {
    type: actionTypes.WALLET_SWAP_SET_CURRENCY,
    payload: { type, value }
  };
}

export function walletSwapSwitch() {
  return { type: actionTypes.WALLET_SWAP_SWITCH };
}

export function walletSwapStartRatePooling() {
  return { type: actionTypes.WALLET_SWAP_START_RATE_POOLING };
}

export function walletSwapStopRatePooling() {
  return { type: actionTypes.WALLET_SWAP_STOP_RATE_POOLING };
}

export function walletSwapSetFocus(payload) {
  return { type: actionTypes.WALLET_SWAP_SET_FOCUS, payload };
}

export function walletSwapSubmit(payload) {
  return { type: actionTypes.WALLET_SWAP_SUBMIT, payload };
}

export function walletUpdate(payload) {
  return { type: actionTypes.WALLET_UPDATE, payload };
}
