import * as actionTypes from "../actions/actionTypes";

const initialState = {
  status: {
    main: "",
    history: "",
    historyMore: "",
    rate: "loading",
    swap: ""
  },
  wallets: [],
  balances: [],
  can_exchange: [],
  refillBankList: [],
  cardReservation: null,
  swap: {
    confirmPayment: "",
    refillBankList: "",
    focus: "from",
    fromCurrency: "idr",
    toCurrency: "btc",
    fromAmount: "",
    toAmount: "",
    rate: 0
  },
  history: {
    next: null,
    items: []
  }
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.WALLET_SET_INIT_STATE: {
      return {
        ...state,
        ...action.payload,
        // history: initialState.history,
        cardReservation: action.payload.card_reservation || null
      };
    }

    case actionTypes.WALLET_SET_STATUS: {
      return {
        ...state,
        status: {
          ...state.status,
          [action.section]: action.status
        }
      };
    }

    case actionTypes.WALLET_SET_SAVING: {
      return {
        ...state,
        wallets: state.wallets.map(wallet =>
          wallet.id === action.payload
            ? {
                ...wallet,
                is_saving_enabled: true
              }
            : wallet
        )
      };
    }

    case actionTypes.WALLET_SET_REFILL_BANK_LIST: {
      return {
        ...state,
        refillBankList: action.banks
      };
    }

    case actionTypes.WALLET_SET_CARD_RESERVATION: {
      return {
        ...state,
        cardReservation: action.payload
      };
    }

    case actionTypes.WALLET_HISTORY_SET: {
      return {
        ...state,
        history: action.payload
      };
    }

    case actionTypes.WALLET_HISTORY_ADD_MORE: {
      return {
        ...state,
        history: {
          next: action.payload.next,
          items: [...state.history.items, ...action.payload.items]
        }
      };
    }

    case actionTypes.WALLET_SWAP_SET_AMOUNT:
    case actionTypes.WALLET_SWAP_UPDATE_AMOUNT: {
      return {
        ...state,
        swap: {
          ...state.swap,
          [action.payload.type + "Amount"]: action.payload.value
        }
      };
    }

    case actionTypes.WALLET_SWAP_SET_RATE: {
      return {
        ...state,
        swap: {
          ...state.swap,
          rate: action.payload
        }
      };
    }

    case actionTypes.WALLET_SWAP_SET_CURRENCY: {
      return {
        ...state,
        swap: {
          ...state.swap,
          [action.payload.type + "Currency"]: action.payload.value
        }
      };
    }

    case actionTypes.WALLET_SWAP_SWITCH: {
      return {
        ...state,
        swap: {
          ...state.swap,
          toAmount: state.swap.fromAmount,
          toCurrency: state.swap.fromCurrency,
          fromAmount: state.swap.toAmount,
          fromCurrency: state.swap.toCurrency,
          focus: state.swap.focus === "from" ? "to" : "from"
        }
      };
    }

    case actionTypes.WALLET_SWAP_SET_FOCUS: {
      return {
        ...state,
        swap: {
          ...state.swap,
          focus: action.payload
        }
      };
    }

    case actionTypes.WALLET_UPDATE: {
      const {
        balance = {},
        wallet = {},
        history,
        transaction,
        transfer
      } = action.payload;

      const newHistoryItem = history || transaction || transfer;

      return {
        ...state,
        history: {
          ...state.history,
          items: newHistoryItem
            ? [newHistoryItem, ...state.history.items]
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

    default:
      return state;
  }
}
