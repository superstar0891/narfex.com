import "./ChangeSecretKeyModal.less";

import React, { useState } from "react";
import { connect } from "react-redux";
import * as UI from "../../../../ui";
import * as utils from "../../../../utils";
import * as profileActions from "../../../../actions/cabinet/profile";

const ChangeSecretKeyModal = props => {
  const [code, setCode] = useState("");
  return (
    <UI.Modal
      isOpen={true}
      onClose={() => {
        props.onClose();
      }}
      width={424}
    >
      <UI.ModalHeader>{utils.getLang("cabinet_enterSecretKey")}</UI.ModalHeader>
      <form
        onSubmit={e => {
          e.preventDefault();
          props.changeSecretKay(code);
        }}
        className="ChangeSecretKayModal"
      >
        <UI.Input
          autoComplete="off"
          autoFocus={true}
          type="password"
          value={code}
          onTextChange={setCode}
          placeholder={utils.getLang("site__authModalSecretKey", true)}
          error={false}
        />
        <div className="ChangeSecretKayModal__submit_wrapper">
          <UI.Button btnType="submit" state={props.state}>
            {utils.getLang("cabinet_settingsSave")}
          </UI.Button>
        </div>
      </form>
    </UI.Modal>
  );
};

export default connect(
  state => ({
    state: state.profile.loadingStatus.secretKey
  }),
  {
    changeSecretKay: profileActions.changeSecretKay
  }
)(ChangeSecretKeyModal);
