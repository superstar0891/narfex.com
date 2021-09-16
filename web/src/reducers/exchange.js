import * as actionTypes from "../actions/actionTypes";
import * as utils from "../utils";

const initialState = {
  loadingStatus: {
    sell: "",
    buy: "",
    default: "loading",
    orderBook: "loading"
  },
  marketConfig: {},
  balances: [],
  trades: [],
  openOrders: {},
  last_orders: {
    items: [],
    next_from: 0
  },
  fee: 0,
  ticker: {
    diff: 0,
    percent: 0
  },
  balanceInfo: {
    primary: {},
    secondary: {}
  },
  market: "btc/usdt",
  markets: [],
  orderBook: [],
  chart: [],
  chartTimeFrame: 5,
  fullscreen: false,
  form: {
    type: "limit",
    buy: {
      price: "",
      amount: "",
      total: ""
    },
    sell: {
      price: "",
      amount: "",
      total: ""
    }
  }
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.EXCHANGE_SET: {
      let ticker = action.ticker || state.ticker;

      let balanceInfo = {};
      let [primary, secondary] = action.market.toUpperCase().split("/");

      balanceInfo.primary = { amount: 0, currency: primary };
      balanceInfo.secondary = { amount: 0, currency: secondary };

      if (action.balances) {
        for (let balance of action.balances) {
          if (balance.currency === primary) {
            balanceInfo.primary = balance;
          } else if (balance.currency === secondary) {
            balanceInfo.secondary = balance;
          }
        }
      }

      let openOrders = {};
      if (action.open_orders) {
        for (let i = 0; i < action.open_orders.length; i++) {
          const order = action.open_orders[i];
          openOrders[order.id] = order;
        }
      }
      //
      // let trades = {};
      // for (let i = 0; i < action.trades.length; i++) {
      //   const order = action.trades[i];
      //   trades[order.id] = order;
      // }

      const currentPrice = utils.formatDouble(
        ticker.price,
        utils.isFiat(secondary) ? 2 : undefined
      );

      return Object.assign({}, state, {
        ...utils.removeProperty(action, "type", "open_orders"),
        ticker,
        balanceInfo,
        openOrders,
        trades: action.trades,
        market: action.market,
        form: {
          ...state.form,
          buy: {
            ...state.form.buy,
            price: currentPrice
          },
          sell: {
            ...state.form.sell,
            price: currentPrice
          }
        }
      });
    }

    case actionTypes.EXCHANGE_SET_LOADING_STATUS: {
      return {
        ...state,
        loadingStatus: {
          ...state.loadingStatus,
          [action.section]: action.status
        }
      };
    }

    case actionTypes.EXCHANGE_ADD_ORDER_HISTORY: {
      return {
        ...state,
        last_orders: {
          items: [...state.last_orders.items, ...action.payload.items],
          next_from: action.payload.next_from
        }
      };
    }

    case actionTypes.EXCHANGE_REMOVE_ORDERS: {
      return {
        ...state,
        orderBook: state.orderBook.filter(o => !action.orderIds.includes(o.id))
      };
    }

    case actionTypes.EXCHANGE_ORDER_BOOK_INIT: {
      return {
        ...state,
        orderBook: [...action.asks, ...action.bids]
      };
    }

    case actionTypes.EXCHANGE_ORDER_BOOK_SELECT_ORDER: {
      const secondaryAction = action.order.action === "buy" ? "sell" : "buy";
      return {
        ...state,
        form: {
          ...state.form,
          [secondaryAction]: {
            ...state.form[secondaryAction],
            ...action.order
          },
          [action.order.action]: {
            ...initialState.form[action.order.action]
          }
        }
      };
    }

    case actionTypes.EXCHANGE_ORDER_BOOK_UPDATE: {
      const openOrders = { ...state.openOrders };

      for (let order of action.orders) {
        if (openOrders[order.id]) {
          openOrders[order.id] = {
            ...openOrders[order.id],
            ...order
          };
        }
      }

      const orderBook = [...state.orderBook];

      action.orders.forEach(order => {
        const index = orderBook.findIndex(o => o.id === order.id);
        if (!!~index) {
          orderBook[index] = order;
        } else {
          orderBook.push(order);
        }
      });

      return {
        ...state,
        openOrders,
        orderBook
      };
    }

    case actionTypes.EXCHANGE_SET_ORDER_STATUS: {
      let openOrders = { ...state.openOrders };
      let lastOrdersItems = [...state.last_orders.items];

      if (openOrders[action.orderId]) {
        let order = { ...openOrders[action.orderId] };
        delete openOrders[action.orderId];
        if (!lastOrdersItems.find(order => order.id === action.orderId)) {
          order.status = action.status;
          lastOrdersItems.unshift(order);
        }
      }

      return {
        ...state,
        openOrders,
        last_orders: {
          ...state.last_orders,
          items: lastOrdersItems
        }
      };
    }

    case actionTypes.EXCHANGE_SET_ORDER_PENDING: {
      return {
        ...state,
        openOrders: {
          ...state.openOrders,
          [action.orderId]: {
            ...state.openOrders[action.orderId],
            status: action.value ? "pending" : ""
          }
        }
      };
    }

    case actionTypes.EXCHANGE_TRADING_FORM_SET_TYPE: {
      return {
        ...state,
        form: {
          ...state.form,
          type: action.payload
        }
      };
    }

    case actionTypes.EXCHANGE_TRADING_FORM_SET_PROPERTIES: {
      return {
        ...state,
        form: {
          ...state.form,
          [action.tradeType]: {
            ...state.form[action.tradeType],
            ...action.properties
          }
        }
      };
    }

    case actionTypes.EXCHANGE_ORDER_COMPLETED: {
      let openOrders = { ...state.openOrders };

      let lastOrdersItems = [
        { ...action.order, status: "completed" },
        ...state.last_orders.items
      ];

      delete openOrders[action.order.id];

      return {
        ...state,
        openOrders,
        last_orders: {
          ...state.last_orders,
          items: lastOrdersItems
        }
      };
    }

    case actionTypes.EXCHANGE_ORDER_FAILED: {
      let order = { ...state.openOrders[action.orderId], status: "failed" };
      let openOrders = { ...state.openOrders };

      delete openOrders[action.orderId];

      let lastOrdersItems = [order, ...state.last_orders.items];

      return {
        ...state,
        openOrders,
        last_orders: {
          ...state.last_orders,
          items: lastOrdersItems
        }
      };
    }

    case actionTypes.EXCHANGE_ADD_OPEN_ORDER: {
      return {
        ...state,
        openOrders: {
          ...state.openOrders,
          [action.order.id]: {
            ...action.order
          }
        }
      };
    }

    case actionTypes.EXCHANGE_ORDER_BOOK_REMOVE_ORDER: {
      const openOrders = { ...state.openOrders };

      action.orders.forEach(orderId => {
        delete openOrders[orderId];
      });

      return {
        ...state,
        orderBook: state.orderBook.filter(o => !action.orders.includes(o.id)),
        openOrders
      };
    }

    case actionTypes.EXCHANGE_ADD_TRADES: {
      return {
        ...state,
        trades: [...action.trades, ...state.trades]
      };
    }

    case actionTypes.EXCHANGE_UPDATE_BALANCE: {
      let balances = Object.assign([], state.balances);
      for (let i = 0; i < balances.length; i++) {
        if (balances[i].currency === action.currency) {
          balances[i].amount = action.amount;
          break;
        }
      }

      let balanceInfo = Object.assign({}, state.balanceInfo);
      const [primary, secondary] = state.market.split("/");

      if (primary === action.currency) {
        balanceInfo.primary.amount = action.amount;
      } else if (secondary === action.currency) {
        balanceInfo.secondary.amount = action.amount;
      }

      return Object.assign({}, state, { balanceInfo, balances });
    }

    case actionTypes.EXCHANGE_CHANGE_TIME_FRAME: {
      return Object.assign({}, state, { chartTimeFrame: action.timeFrame });
    }

    case actionTypes.EXCHANGE_SET_MARKETS: {
      return {
        ...state,
        markets: action.markets
      };
    }

    case actionTypes.EXCHANGE_SET_FULLSCREEN: {
      return {
        ...state,
        fullscreen: action.status
      };
    }

    case actionTypes.EXCHANGE_TICKER_UPDATE: {
      return {
        ...state,
        ticker: {
          ...state.ticker,
          ...action.ticker,
          prevPrice: state.ticker.price
        }
      };
    }

    default:
      return state;
  }
}
