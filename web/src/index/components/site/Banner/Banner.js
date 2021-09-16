import "./Banner.less";

import React from "react";

import * as UI from "../../../../ui";
import * as actions from "../../../../actions";

function Banner({ title, caption, btnText }) {
  return (
    <div className="Banner">
      <h2 className="Banner__title">{title}</h2>
      <p className="Banner__caption">{caption}</p>
      {/* TODO: control the wide (CTA) buttons via props */}
      <UI.Button
        onClick={() => actions.openModal("registration")}
        fontSize={15}
        rounded
        type="secondary"
        style={{ width: 240 }}
      >
        {btnText}
      </UI.Button>
    </div>
  );
}

export default React.memo(Banner);
