import "./AuthModal.less";

import React, { useState, memo, useCallback, useEffect } from "react";
import cn from "classnames";
import { useRouter, useRoute } from "react-router5";
import { useSelector, useDispatch } from "react-redux";
import {
  Button,
  ButtonWrapper,
  CheckBox,
  Input,
  Modal,
  ModalHeader
} from "../../ui";
import { ReactComponent as GACodeIcon } from "src/asset/google_auth.svg";
import Lang from "src/components/Lang/Lang";
import Captcha from "../Captcha/Captcha";
import {
  authSelector,
  authStatusSelector,
  userSelector
} from "../../selectors";
import {
  authClearState,
  authSendEmail,
  authSetCode,
  authSetCSRFToken,
  authSetEmail,
  authSetGaCode,
  authSetRecaptchaResponse,
  authVerifyAuthCode
} from "../../actions/auth";
import * as firebase from "firebase";
import * as pages from "../../index/constants/pages";
import * as utils from "../../utils";
import * as actions from "../../actions";
import REGEXES from "../../index/constants/regexes";
import { getLang } from "../../utils";
import * as toasts from "../../actions/toasts";

export default memo(({ onClose }) => {
  const [check, setCheck] = useState(false);
  const [touched, setTouched] = useState(false);
  const dispatch = useDispatch();
  const router = useRouter();
  const {
    route: {
      params: { modal: modalName }
    }
  } = useRoute();
  const isLogin = modalName === "login";
  const user = useSelector(userSelector);
  const {
    email,
    csrfToken,
    incorrectAuthCode,
    incorrectGACode,
    recaptchaResponse,
    code,
    gaCode,
    resendTimeout,
    needGaCode
  } = useSelector(authSelector);
  const validEmail = REGEXES.email.test(email);
  const validGaCode = gaCode?.toString().length === 6;
  const validCode = code?.toString().length === 6;
  const sendEmailStatus = useSelector(authStatusSelector("sendEmail"));
  const verifyAuthCodeStatus = useSelector(
    authStatusSelector("verifyAuthCode")
  );

  useEffect(() => {
    firebase
      .analytics()
      .logEvent(isLogin ? "open_login_modal" : "open_registration_modal");
  }, [isLogin]);

  useEffect(() => {
    setTouched(false);
  }, [needGaCode, csrfToken, recaptchaResponse]);

  useEffect(() => {
    if (user) {
      router.navigate(pages.CABINET);
      dispatch(authClearState());
      onClose();
    }
  }, [dispatch, user, router, onClose]);

  useEffect(() => {
    return () => {
      dispatch(authClearState());
    };
  }, [dispatch]);

  const handleChangeEmail = useCallback(
    email => {
      dispatch(authSetEmail(email));
    },
    [dispatch]
  );

  const handleChangeRecaptchaResponse = useCallback(
    hash => {
      dispatch(authSetRecaptchaResponse(hash));
    },
    [dispatch]
  );

  const handleSendEmail = useCallback(() => {
    if (validEmail && !resendTimeout && (isLogin || check)) {
      dispatch(authSendEmail());
    } else {
      if (resendTimeout) {
        toasts.error(
          getLang("cabinet_auth_resendTimeoutError").replace(
            /{.*?}/g,
            resendTimeout
          )
        );
      }
      setTouched(true);
    }
  }, [validEmail, isLogin, dispatch, resendTimeout, check]);

  const handleResendEmail = useCallback(() => {
    dispatch(authSetRecaptchaResponse(null));
    dispatch(authSetCSRFToken(null));
  }, [dispatch]);

  const handleSetCode = useCallback(
    code => {
      dispatch(authSetCode(code.replace(/\D/g, "").substr(0, 6)));
    },
    [dispatch]
  );

  const handleSetGaCode = useCallback(
    code => {
      dispatch(authSetGaCode(code.replace(/\D/g, "").substr(0, 6)));
    },
    [dispatch]
  );

  const handleVerifyAuthCode = useCallback(() => {
    if (validCode) {
      dispatch(authVerifyAuthCode());
    } else {
      setTouched(true);
    }
  }, [dispatch, validCode]);

  const handleVerifyGACode = useCallback(() => {
    if (validGaCode) {
      dispatch(authVerifyAuthCode());
    } else {
      setTouched(true);
    }
  }, [dispatch, validGaCode]);

  const handleCheck = useCallback(() => {
    setCheck(!check);
  }, [check]);

  return (
    <Modal className="AuthModal" onClose={onClose}>
      {!csrfToken ? (
        <>
          <ModalHeader>
            <Lang name="cabinet_auth_enterYouEmailTitle" />
          </ModalHeader>
          <p>
            <Lang
              name={
                isLogin
                  ? "cabinet_auth_enterYouEmailSubtitle"
                  : "cabinet_auth_registration_enterYouEmailSubtitle"
              }
            />
          </p>
          <Input
            error={touched && !validEmail}
            autoComplete="email"
            placeholder="youemail@example.com"
            value={email}
            onTextChange={handleChangeEmail}
          />
          {!isLogin && (
            <CheckBox
              error={touched && !check}
              checked={check}
              onChange={handleCheck}
            >
              <Lang
                name="cabinet_auth_acceptTerms"
                params={{
                  link: (
                    <span
                      onClick={() =>
                        actions.openStateModal("static_content", {
                          type: "terms"
                        })
                      }
                      className="link"
                    >
                      <Lang name="cabinet_auth_linkTerms" />
                    </span>
                  )
                }}
              />
            </CheckBox>
          )}
          <Captcha
            error={touched && !recaptchaResponse}
            onChange={handleChangeRecaptchaResponse}
          />
          <ButtonWrapper align="center">
            <Button onClick={handleSendEmail} state={sendEmailStatus}>
              <Lang name="cabinet_auth_sendCodeButton" />
            </Button>
          </ButtonWrapper>
        </>
      ) : !needGaCode ? (
        <>
          <ModalHeader>
            <Lang name="cabinet_auth_enterGaTitle" />
          </ModalHeader>
          <p>
            <Lang
              name="cabinet_auth_enterGaSubtitle"
              params={{
                email: <b>{email}</b>
              }}
            />
          </p>

          <Input
            error={incorrectAuthCode || (touched && !validCode)}
            placeholder={"123456"}
            type="text"
            value={code || ""}
            onTextChange={handleSetCode}
          />

          <p>
            <Lang
              name="cabinet_auth_resendText"
              params={{
                action: resendTimeout ? (
                  <Lang
                    name="cabinet_auth_resendText_wait"
                    params={{
                      sec: resendTimeout
                    }}
                  />
                ) : (
                  <span
                    className={cn("link", { disabled: sendEmailStatus })}
                    onClick={handleResendEmail}
                  >
                    <Lang name="cabinet_auth_resendText_button" />
                  </span>
                )
              }}
            />
          </p>

          <ButtonWrapper align="center">
            <Button onClick={handleVerifyAuthCode} state={verifyAuthCodeStatus}>
              <Lang
                name={
                  isLogin
                    ? "cabinet_auth_loginButton"
                    : "cabinet_auth_registrationButton"
                }
              />
            </Button>
          </ButtonWrapper>
        </>
      ) : (
        <>
          <ModalHeader>GA Code</ModalHeader>

          <Input
            autoFocus
            type="text"
            autoComplete="off"
            value={gaCode}
            error={incorrectGACode || (touched && !validGaCode)}
            onTextChange={handleSetGaCode}
            placeholder={utils.getLang("site__authModalGAPlaceholder", true)}
            indicator={<GACodeIcon />}
          />
          <ButtonWrapper align="center">
            <Button onClick={handleVerifyGACode} state={verifyAuthCodeStatus}>
              <Lang
                name={
                  isLogin
                    ? "cabinet_auth_loginButton"
                    : "cabinet_auth_registrationButton"
                }
              />
            </Button>
          </ButtonWrapper>
        </>
      )}
    </Modal>
  );
});
