import "./Resend.less";

import React, { useState } from "react";

import * as utils from "../../../../utils";

function Resend({ onResend }) {
  const [timeRemaining, updateTiming] = useState(60);
  const [isRunning, setIsRunning] = useState(true);
  const [isActiveResend, activateResend] = useState(false);

  const handleResend = () => {
    onResend();
    updateTiming(60);
    setIsRunning(true);
    activateResend(false);
  };

  utils.useInterval(
    () => {
      if (timeRemaining === 0) {
        setIsRunning(false);
        activateResend(true);
      } else {
        updateTiming(timeRemaining - 1);
      }
    },
    isRunning ? 1000 : null
  );

  return (
    <p
      onClick={isActiveResend ? handleResend : null}
      className={"Resend " + (isActiveResend ? "Resend__active" : "")}
    >
      {utils.getLang("site__authModalResend") +
        (!isActiveResend ? ` ${timeRemaining}s` : "")}
    </p>
  );
}

export default React.memo(Resend);
