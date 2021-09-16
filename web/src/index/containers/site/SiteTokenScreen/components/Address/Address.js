import "./Address.less";
import React from "react";
import { getLang } from "src/utils";
import Clipboard from "src/index/components/cabinet/Clipboard/Clipboard";

export default props => {
  return (
    <div className="SiteTokenScreen__Address">
      <div className="anchor" id="Address" />
      <h2>{getLang("token_AddressTitle")}</h2>
      <div className="SiteTokenScreen__Address__Clipboard">
        <Clipboard
          text={"0x83869de76b9ad8125e22b857f519f001588c0f62"}
        ></Clipboard>
      </div>
    </div>
  );
};
