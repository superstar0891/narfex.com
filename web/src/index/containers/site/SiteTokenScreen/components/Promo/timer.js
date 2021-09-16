import React, { useEffect, useState } from "react";
import { getLang } from "src/utils";

export default props => {
  const currentTime = Date.now();
  const currentStep = props.roadMap.filter(s => s.time < currentTime).pop();
  const nextStep = props.roadMap.find(s => s.time > currentTime);

  if (!nextStep) {
    return null;
  }

  const diff = nextStep.time - currentTime;
  const [timeLeft, setTimeLeft] = useState(calculateTimeLeft(diff));

  useEffect(() => {
    setTimeout(() => {
      setTimeLeft(calculateTimeLeft(diff));
    }, 1000);
  });

  return timeLeft ? (
    <div className="SiteTokenScreen__Promo__numbers__item">
      <small>{currentStep.title}</small>
      <strong>
        {timeLeft.days}
        {getLang("global_D")} {timeLeft.hours}
        {getLang("global_H")} {timeLeft.minutes}
        {getLang("global_M")}{" "}
      </strong>
    </div>
  ) : null;
};

export const calculateTimeLeft = difference => {
  return difference > 0
    ? {
        days: Math.floor(difference / (1000 * 60 * 60 * 24)),
        hours: Math.floor((difference / (1000 * 60 * 60)) % 24),
        minutes: Math.floor((difference / 1000 / 60) % 60),
        seconds: Math.floor((difference / 1000) % 60)
      }
    : false;
};
