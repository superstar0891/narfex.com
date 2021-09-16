import "./DynamicModal.less";

import React from "react";
import * as UI from "../ui";
import Item from "./components/Item/Item";
import { closeModal } from "../actions/admin";

export default props => {
  return Object.keys(props.modals).map(name => {
    const modal = props.modals[name];

    return (
      modal.visible && (
        <UI.Modal
          className="DynamicModal"
          isOpen={true}
          onClose={() => closeModal(name)}
        >
          {modal.layout.map((item, key) => (
            <Item item={item} key={key} />
          ))}
        </UI.Modal>
      )
    );
  });
};
