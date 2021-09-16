import "./Toasts.less";
import React from "react";
import { connect } from "react-redux";
import Toast from "../../../../ui/components/Toast/Toast";
import * as toastActions from "../../../../actions/toasts";

function Toasts(props) {
  return (
    <div className="Toasts">
      {props.toasts.items.map(toast => (
        <Toast
          onMouseOver={() => toastActions.setHide(toast.id, false)}
          onMouseLeave={() => toastActions.setHide(toast.id, true)}
          type={toast.type}
          key={toast.id}
          message={toast.message}
          hidden={toast.hidden}
          onClose={() => props.toastDrop(toast.id)}
        />
      ))}
    </div>
  );
}

export default connect(
  state => ({
    toasts: state.toasts
  }),
  {
    toastDrop: toastActions.toastDrop
  }
)(Toasts);
