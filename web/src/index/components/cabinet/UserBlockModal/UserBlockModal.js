import "./UserBlockModal.less";

import React from "react";
import { connect } from "react-redux";
import * as UI from "../../../../ui";
import SVG from "react-inlinesvg";
import * as utils from "../../../../utils";
import company from "../../../constants/company";
import Button from "../../../../ui/components/Button/Button";

export default connect(state => ({ adaptive: state.default.adaptive }))(
  props => {
    return (
      <UI.Modal isOpen={true} onClose={props.onClose}>
        {props.adaptive && (
          <UI.ModalHeader>
            {utils.getLang("cabinet_withdrawalDisabledTitle")}
          </UI.ModalHeader>
        )}
        <div className="UserBlockModal">
          <SVG src={require("../../../../asset/120/block.svg")} />
          <p>
            {utils.getLang("cabinet_withdrawalDisabledText")}{" "}
            <a href={"mailto:" + company.email.support}>
              {company.email.support}
            </a>
          </p>
          <Button onClick={props.onClose}>{utils.getLang("global_ok")}</Button>
        </div>
      </UI.Modal>
    );
  }
);
