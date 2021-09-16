import * as actionTypes from "../actions/actionTypes";

const initialState = {
  status: {
    main: "",
    buy: ""
  },
  periods: [],
  current_period: 0,
  promoCode: "",
  amount: 10000,
  promo_code_reward_percent: 0,
  currency: "btc"
};

export default function reduce(state = initialState, action = {}) {
  const { type, payload } = action;
  switch (type) {
    case actionTypes.TOKEN_SET:
      return {
        ...state,
        ...payload
      };

    case actionTypes.TOKEN_SET_CURRENCY:
      return {
        ...state,
        currency: payload
      };

    case actionTypes.TOKEN_SET_AMOUNT:
      return {
        ...state,
        amount: payload
      };

    case actionTypes.TOKEN_SET_PROMOCODE:
      return {
        ...state,
        promoCode: payload
      };

    case actionTypes.TOKEN_SET_STATUS:
      return {
        ...state,
        status: {
          ...state.status,
          [payload.type]: payload.value
        }
      };

    default:
      return state;
  }
}
