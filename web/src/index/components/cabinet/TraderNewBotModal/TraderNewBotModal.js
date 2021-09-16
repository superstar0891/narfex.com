import "./TraderNewBotModal.less";

import React, { useState } from "react";
import { connect } from "react-redux";

import * as actions from "../../../../actions/cabinet/trader";

import Modal from "../../../../ui/components/Modal/Modal";
import * as UI from "../../../../ui";

function FiatOperationModal(props) {
  const [name, setName] = useState();

  const __handleSubmitForm = e => {
    e.preventDefault();
    props.createBot(name);
  };

  return (
    <Modal className="TraderNewBotModal" isOpen={true} onClose={props.onClose}>
      <UI.ModalHeader>Create New Bot</UI.ModalHeader>
      <div>
        <form onSubmit={__handleSubmitForm}>
          <UI.Input
            value={name}
            onTextChange={setName}
            placeholder="Enter Bot Name"
          />
          <UI.Button onSubmit>Create</UI.Button>
        </form>
      </div>
    </Modal>
  );
}

export default connect(state => ({}), {
  createBot: actions.createBot
})(FiatOperationModal);
