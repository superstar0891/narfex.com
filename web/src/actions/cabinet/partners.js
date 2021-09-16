import * as actionTypes from "src/actions/actionTypes";

export const partnersFetch = () => ({
  type: actionTypes.PARTNERS_FETCH
});

export const partnersInit = payload => ({
  type: actionTypes.PARTNERS_INIT,
  payload
});

export const partnersFetchHistoryMore = () => ({
  type: actionTypes.PARTNERS_FETCH_HISTORY_MORE
});

export const partnersAddHistory = payload => ({
  type: actionTypes.PARTNERS_ADD_HISTORY,
  payload
});

export const partnersAddNewTransaction = payload => ({
  type: actionTypes.PARTNERS_ADD_NEW_TRANSACTION,
  payload
});

export const partnersSetStatus = (name, value) => ({
  type: actionTypes.PARTNERS_SET_STATUS,
  payload: {
    name,
    value
  }
});

export const partnersUpdateBalance = balance => ({
  type: actionTypes.PARTNERS_UPDATE_BALANCE,
  payload: balance
});

export const partnersBalanceWithdrawal = (id, amount) => ({
  type: actionTypes.PARTNERS_BALANCE_WITHDRAWAL,
  payload: {
    id,
    amount
  }
});
