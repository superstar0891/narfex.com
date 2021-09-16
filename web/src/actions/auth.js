import * as actionTypes from "./actionTypes";

export function authSetCode(payload) {
  return {
    type: actionTypes.AUTH_SET_CODE,
    payload
  };
}
export function authSetGaCode(payload) {
  return {
    type: actionTypes.AUTH_SET_GA_CODE,
    payload
  };
}

export function authSetEmail(payload) {
  return {
    type: actionTypes.AUTH_SET_EMAIL,
    payload
  };
}

export function authSetResendTimeout(payload) {
  return {
    type: actionTypes.AUTH_SET_RESEND_TIMEOUT,
    payload
  };
}

export function authSetRecaptchaResponse(payload) {
  return {
    type: actionTypes.AUTH_SET_RECAPTCHA_RESPONSE,
    payload
  };
}

export function authSetCSRFToken(payload) {
  return {
    type: actionTypes.AUTH_SET_CSRF_TOKEN,
    payload
  };
}

export function authSetStatus(section, status) {
  return {
    type: actionTypes.AUTH_SET_STATUS,
    payload: { section, status }
  };
}

export function authSendEmail() {
  return { type: actionTypes.AUTH_SEND_EMAIL };
}

export function authVerifyAuthCode() {
  return { type: actionTypes.AUTH_VERIFY_AUTH_CODE };
}

export function authStartTimer() {
  return { type: actionTypes.AUTH_START_TIMER };
}

export function authSetNeedGaCode(value) {
  return { type: actionTypes.AUTH_SET_NEED_GA_CODE, payload: value };
}

export function authClearState() {
  return { type: actionTypes.AUTH_CLEAR_STATE };
}

export function authSetIncorrectAuthCode(payload) {
  return { type: actionTypes.AUTH_SET_INCORRECT_AUTH_CODE, payload };
}

export function authSetIncorrectGaCode(payload) {
  return { type: actionTypes.AUTH_SET_INCORRECT_GA_CODE, payload };
}
