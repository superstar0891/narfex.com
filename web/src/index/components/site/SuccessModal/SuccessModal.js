import "./SuccessModal.less";

import React, { useState } from "react";

import * as UI from "../../../../ui";
import * as utils from "../../../../utils";
import Resend from "../Resend/Resend";
import Captcha from "../../../../components/Captcha/Captcha";

function SuccessModal({ title, subtitle, onClose, onResend }) {
  const [displayCaptcha, setDisplayCaptcha] = useState(false);

  const handleResend = () => {
    setDisplayCaptcha(true);
  };

  const handleCaptchaChange = token => {
    setDisplayCaptcha(false);
    onResend(token);
  };

  return (
    <div className="SuccessModal">
      <div className="SuccessModal__content">
        <img
          src={require("../../../../asset/site/success_tick.svg")}
          alt="Success"
          className="SuccessModal__tick"
        />

        {!!title && <p className="SuccessModal__content__title">{title}</p>}
        {!!subtitle && <p className="SuccessModal__content__msg">{subtitle}</p>}
      </div>

      <div className="Resend__footer">
        {displayCaptcha ? (
          <Captcha onChange={handleCaptchaChange} />
        ) : (
          <>
            {!!onResend && <Resend onResend={handleResend} />}
            <UI.Button fontSize={15} onClick={onClose}>
              {utils.getLang("site__authModalOk")}
            </UI.Button>
          </>
        )}
      </div>
    </div>
  );
}

export default React.memo(SuccessModal);
