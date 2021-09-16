import * as actionTypes from "../actions/actionTypes";

const initialState = {
  csrfToken: null,
  needGaCode: false,
  recaptchaResponse: null,
  resendTimeout: 0,
  incorrectAuthCode: false,
  incorrectGACode: false,
  email: "",
  code: null,
  gaCode: null,
  status: {
    sendEmail: "",
    verifyAuthCode: ""
  }
};

export default function reduce(state = initialState, { type, payload }) {
  switch (type) {
    case actionTypes.AUTH_SET_STATUS: {
      return {
        ...state,
        status: {
          ...state.status,
          [payload.section]: payload.status
        }
      };
    }

    case actionTypes.AUTH_SET_CSRF_TOKEN: {
      return {
        ...state,
        csrfToken: payload
      };
    }

    case actionTypes.AUTH_SET_RECAPTCHA_RESPONSE: {
      return {
        ...state,
        recaptchaResponse: payload
      };
    }

    case actionTypes.AUTH_SET_RESEND_TIMEOUT: {
      return {
        ...state,
        resendTimeout: payload
      };
    }

    case actionTypes.AUTH_SET_EMAIL: {
      return {
        ...state,
        email: payload
      };
    }

    case actionTypes.AUTH_SET_CODE: {
      return {
        ...state,
        code: payload,
        incorrectAuthCode: false
      };
    }

    case actionTypes.AUTH_SET_GA_CODE: {
      return {
        ...state,
        gaCode: payload,
        incorrectGACode: false
      };
    }

    case actionTypes.AUTH_SET_INCORRECT_GA_CODE: {
      return {
        ...state,
        incorrectGACode: payload
      };
    }

    case actionTypes.AUTH_SET_INCORRECT_AUTH_CODE: {
      return {
        ...state,
        incorrectAuthCode: payload
      };
    }

    case actionTypes.AUTH_SET_NEED_GA_CODE: {
      return {
        ...state,
        needGaCode: payload
      };
    }

    case actionTypes.AUTH_CLEAR_STATE: {
      return initialState;
    }

    default:
      return state;
  }
}
