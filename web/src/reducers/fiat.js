import * as actionTypes from "../actions/actionTypes";

const initialState = {
  balances: [],
  wallets: [],
  history: {
    next: 0,
    items: []
  },
  rates: {},
  rate: 0,
  rateType: "update",
  rateUpdateTime: 0,
  merchants: [],
  merchantType: null,
  exchange_fee: 0,
  withdrawalBankList: null,
  refillBankList: [],
  market: "",
  reservedCard: null,
  loadingStatus: {
    confirmPayment: "",
    cancelReservation: "",
    reservedCard: "",
    withdrawalBankList: "",
    refillBankList: "",
    reservation: "",
    default: "loading",
    merchants: "loading",
    rate: "",
    newRate: "",
    marketForm: "",
    history: ""
  }
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.FIAT_WALLETS_SET: {
      return {
        ...state,
        ...action.payload,
        reservedCard: action.payload.card_reservation,
        pending: false
      };
    }

    case actionTypes.FIAT_HISTORY_ADD_ITEMS: {
      return {
        ...state,
        history: {
          next: action.payload.next,
          items: [...state.history.items, ...action.payload.items]
        }
      };
    }

    case actionTypes.FIAT_SET_RESERVED_CARD: {
      return {
        ...state,
        reservedCard: action.payload
      };
    }

    case actionTypes.FIAT_WALLETS_UPDATE: {
      const { balance, wallet } = action.payload;
      return {
        ...state,
        history: {
          ...state.history,
          items: action.payload.history
            ? [action.payload.history, ...state.history.items]
            : state.history.items
        },
        balances: balance
          ? state.balances.map(b =>
              b.id === balance.id ? { ...b, ...balance } : b
            )
          : state.balances,
        wallets: wallet
          ? state.wallets.map(w =>
              w.id === wallet.id ? { ...w, ...wallet } : w
            )
          : state.wallets
      };
    }

    case actionTypes.FIAT_WALLETS_SET_MARKET_EXCHANGE: {
      return {
        ...state,
        market: action.payload
      };
    }

    case actionTypes.FIAT_WALLETS_SET_LOADING_STATUS: {
      return {
        ...state,
        loadingStatus: {
          ...state.loadingStatus,
          [action.section]: action.status
        }
      };
    }

    case actionTypes.FIAT_WALLETS_CLEAR_LOADING_STATUSES: {
      return {
        ...state,
        loadingStatus: {
          ...initialState.loadingStatus,
          default: state.loadingStatus.default
        }
      };
    }

    case actionTypes.FIAT_WALLETS_SET_RATE: {
      return {
        ...state,
        rate: action.rate,
        rateUpdateTime: action.uprateTime
      };
    }

    case actionTypes.FIAT_WALLETS_SET_MERCHANTS: {
      return {
        ...state,
        merchantType: action.merchantType,
        merchants: action.methods
      };
    }

    case actionTypes.FIAT_WALLETS_SET_WITHDRAWAL_BANK_LIST: {
      return {
        ...state,
        withdrawalBankList: action.banks
      };
    }

    case actionTypes.FIAT_WALLETS_SET_REFILL_BANK_LIST: {
      return {
        ...state,
        refillBankList: action.banks
      };
    }

    case actionTypes.FIAT_WALLETS_APPEND_TRANSACTION: {
      return {
        ...state,
        history: {
          ...state.history,
          items: [action.transaction, ...state.history.items]
        }
      };
    }

    default:
      return state;
  }
}
