import "./InfoCard.less";

import React from "react";

import { classNames } from "../../../../utils";

function InfoCard({ title, caption, icon, btn, className, horizontal }) {
  const InfoCardClassName = classNames({
    InfoCard: true,
    [className]: !!className,
    horizontal
  });

  return (
    <div className={InfoCardClassName}>
      <div
        className="InfoCard__icon"
        style={{ backgroundImage: `url(${icon})` }}
      />
      <div className="InfoCard__cont">
        <h3 className="InfoCard__title">{title}</h3>
        <p className="InfoCard__caption">{caption}</p>

        {btn || null}
      </div>
    </div>
  );
}

export default React.memo(InfoCard);
