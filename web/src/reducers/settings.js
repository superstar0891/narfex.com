import * as actionTypes from "../actions/actionTypes";
import * as storage from "../services/storage";

const initialState = {
  user: {
    old_password: "",
    new_password: "",
    re_password: ""
  },
  floodControl: storage.getItem("floodControl") || false,
  loadingStatus: {},
  translator: storage.getItem("translatorMode") || false,
  translatorLangCode: storage.getItem("translatorLangCode") || "en"
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.SETTINGS_SET_LOADING_STATUS: {
      return Object.assign({}, state, {
        loadingStatus: Object.assign({}, state.loadingStatus, {
          [action.section]: action.status
        })
      });
    }

    case actionTypes.SETTINGS_USER_FIELD_SET: {
      return Object.assign({}, state, {
        user: {
          ...state.user,
          [action.field]: action.value
        }
      });
    }

    case actionTypes.SETTINGS_SET: {
      return Object.assign({}, state, {
        user: {
          ...state.user,
          ...action.user
        }
      });
    }

    case actionTypes.TRANSLATOR_TOGGLE: {
      return {
        ...state,
        translator: action.value
      };
    }

    case actionTypes.TRANSLATOR_FLOOD_CONTROL: {
      return {
        ...state,
        floodControl: action.value
      };
    }

    case actionTypes.TRANSLATOR_SET_LANG_CODE: {
      return {
        ...state,
        translatorLangCode: action.code
      };
    }

    case actionTypes.APIKEY_SET: {
      const { apikey } = action;
      const items = state.dataApiKeys;
      const apiKeys = apikey.keys ? [...apikey.keys] : [];
      const apiKey = apikey.key ? [apikey.key] : [];
      const dataApiKeys =
        apiKey.length !== 0 && items
          ? [...items, ...apiKey]
          : apiKeys.map(item =>
              item.allow_ips !== ""
                ? {
                    ...item,
                    addIpAddress: true,
                    radioCheck: "second",
                    allow_ips: item.allow_ips.split(",").map(ip => {
                      return { address: ip, touched: false };
                    })
                  }
                : item
            );
      return {
        ...state,
        dataApiKeys
      };
    }

    case actionTypes.SECRETKEY_SET: {
      const { secret_key, key_id } = action;
      const dataApiKeys = state.dataApiKeys.map(item =>
        item.id === key_id
          ? { ...item, secret_key, displaySecretKey: true }
          : item
      );
      return {
        ...state,
        dataApiKeys
      };
    }

    case actionTypes.IS_SECRETKEY: {
      const dataApiKeys = state.dataApiKeys.map(item =>
        item.secret_key
          ? { ...item, secret_key: null, displaySecretKey: false }
          : item
      );
      return {
        ...state,
        dataApiKeys
      };
    }

    case actionTypes.SETTINGS_CHECK_TRADING: {
      const { id, permission_trading } = action;
      const dataApiKeys = state.dataApiKeys.map(item => {
        if (item.id === id) {
          if (!permission_trading || item.permission_withdraw) {
            return {
              ...item,
              permission_trading: !permission_trading,
              canSave: true
            };
          }
          return {
            ...item,
            permission_trading: !permission_trading,
            canSave: false
          };
        }
        return item;
      });

      return {
        ...state,
        dataApiKeys
      };
    }

    case actionTypes.SETTINGS_CHECK_WITHDRAW: {
      const { id, permission_withdraw } = action;
      const dataApiKeys = state.dataApiKeys.map(item => {
        if (item.id === id) {
          if (!permission_withdraw || item.permission_trading) {
            return {
              ...item,
              permission_withdraw: !permission_withdraw,
              canSave: true
            };
          }
          return {
            ...item,
            permission_withdraw: !permission_withdraw,
            canSave: false
          };
        }
        return item;
      });

      return {
        ...state,
        dataApiKeys
      };
    }

    case actionTypes.SETTINGS_IP_ACCESS: {
      const { key_id, radio } = action;
      const dataApiKeys = state.dataApiKeys.map(item => {
        if (item.id === key_id) {
          const allow_ips = item.allow_ips;
          if (radio === "first") {
            return {
              ...item,
              radioCheck: radio,
              addIpAddress: false,
              canSave: false
            };
          }
          if (Array.isArray(item.allow_ips)) {
            return {
              ...item,
              radioCheck: radio,
              addIpAddress: true,
              canSave: true
            };
          }
          return {
            ...item,
            radioCheck: radio,
            allow_ips: allow_ips.split(","),
            addIpAddress: true,
            canSave: true
          };
        }
        return item;
      });

      return {
        ...state,
        dataApiKeys
      };
    }

    case actionTypes.SETTINGS_IP_ADDRESS_FIELD_SET: {
      const { key_id, id_ip } = action;
      const dataApiKeys = state.dataApiKeys.map(item =>
        item.id === key_id
          ? {
              ...item,
              canSave: true,
              allow_ips: item.allow_ips.map((data_ip, i) =>
                i === id_ip ? { address: action.value, touched: true } : data_ip
              )
            }
          : item
      );
      return {
        ...state,
        dataApiKeys
      };
    }

    case actionTypes.ADD_IP_ADDRESS: {
      const { key_id } = action;
      const dataApiKeys = state.dataApiKeys.map(item =>
        item.id === key_id
          ? {
              ...item,
              allow_ips: item.allow_ips.concat({ address: "", touched: false }),
              canSave: true
            }
          : item
      );
      return {
        ...state,
        dataApiKeys
      };
    }

    case actionTypes.DELETE_IP_ADDRESS: {
      const { key_id, id_ip } = action;
      const dataApiKeys = state.dataApiKeys.map(item =>
        item.id === key_id
          ? {
              ...item,
              canSave: true,
              allow_ips: item.allow_ips.filter((data_ip, i) => i !== id_ip)
            }
          : item
      );
      return {
        ...state,
        dataApiKeys
      };
    }

    default:
      return state;
  }
}
