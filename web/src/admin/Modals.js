// styles
// external
import React from "react";
import { connect } from "react-redux";
// internal
import AuthModal from "../components/AuthModal/AuthModal";
import ConfirmModal from "../index/components/cabinet/ConfirmModal/ConfirmModal";
import { closeModal } from "../actions";
import GAConfirmModal from "../index/components/cabinet/GAConfirmModal/GAConfirmModal";

function Modals(props) {
  const routerParams = props.route.params;
  delete routerParams.ref;
  const { options } = props.route.meta;
  const modal = props.modal.name || routerParams.modal;

  let Component = false;

  switch (modal) {
    case "test":
      Component = () => <div>Test</div>;
      break;
    case "login":
      Component = AuthModal;
      break;
    case "confirm":
      Component = ConfirmModal;
      break;
    case "ga_code":
      Component = GAConfirmModal;
      break;
    default:
      return null;
  }

  return (
    <Component
      {...routerParams}
      {...props.modal.params}
      {...options}
      onBack={() => {
        window.history.back();
      }}
      onClose={() => {
        closeModal();
      }}
    />
  );
}

export default connect(state => ({
  route: state.router.route,
  modal: state.modal
}))(Modals);
