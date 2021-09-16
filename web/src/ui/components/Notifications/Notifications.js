// styles
import "./Notifications.less";
// external
import React from "react";
import PropTypes from "prop-types";
// internal
import * as UI from "../../index";
import MarkDown from "../MarkDown/MarkDown";
import ScrollBox from "../ScrollBox/ScrollBox";
import { classNames } from "../../utils";
import LoadingStatus from "../../../index/components/cabinet/LoadingStatus/LoadingStatus";
import SVG from "react-inlinesvg";

export function Notification(props) {
  return (
    <div
      className={classNames(props.classNames, "Notification", {
        unread: props.unread
      })}
    >
      <div
        className="Notification__icon"
        style={{
          backgroundImage: props.icon && `url(${props.iconUrl})`,
          color: props.iconFill
        }}
      >
        {typeof props.icon === "string" ? (
          <img alt="" src={props.icon} />
        ) : (
          <SVG src={props.icon} />
        )}
      </div>
      <div className="Notification__body">
        <div className="Notification__message">{props.message}</div>
        {!!props.markText && (
          <MarkDown lassName="Notification__text" content={props.markText} />
        )}
        {props.actions && (
          <div className="Notification__body__buttons">
            {props.actions.map((action, key) => (
              <UI.Button
                key={key}
                type={action.type}
                size="ultra_small"
                onClick={e => props.onAction(action)}
              >
                {action.text}
              </UI.Button>
            ))}
          </div>
        )}
      </div>
      <div className="Notification__date">{props.date}</div>
    </div>
  );
}

Notification.propTypes = {
  unread: PropTypes.bool,
  classNames: PropTypes.string,
  message: PropTypes.string,
  icon: PropTypes.string,
  iconUrl: PropTypes.string,
  iconFill: PropTypes.string,
  markText: PropTypes.string,
  actions: PropTypes.array
};

export function NotificationSeparator(props) {
  return <h4 className="NotificationSeparator">{props.title}</h4>;
}

NotificationSeparator.propTypes = {
  title: PropTypes.string
};

export default class Notifications extends React.Component {
  constructor(props) {
    super(props);
    this.handleClickEsc = this.handleClickEsc.bind(this);
  }

  componentDidMount() {
    document.addEventListener("keydown", this.handleClickEsc, false);
    document.addEventListener("click", this.handleClick, false);
  }

  handleClick = e => {
    if (
      this.refs.notifications &&
      !this.refs.notifications.contains(e.target) &&
      this.props.onClose
    ) {
      this.props.onClose();
    }
  };

  componentWillUnmount() {
    document.removeEventListener("keydown", this.handleClickEsc, false);
    document.removeEventListener("click", this.handleClick, false);
  }

  handleClickEsc(e) {
    if (e.keyCode === 27) {
      this.props.onClose && this.props.onClose();
    }
  }

  render() {
    const empty =
      !(this.props.children && this.props.children.length) ||
      this.props.pending;
    return (
      <div
        ref="notifications"
        className={classNames("Notifications", this.props.classNames, {
          empty: empty,
          inline: this.props.inline
        })}
      >
        <ScrollBox>
          {this.props.pending && <LoadingStatus inline status="loading" />}
          {!this.props.pending &&
            (!empty ? (
              this.props.children
            ) : (
              <span className="Notifications__empty_text">
                {this.props.emptyText}
              </span>
            ))}
        </ScrollBox>
      </div>
    );
  }
}

Notifications.propTypes = {
  classNames: PropTypes.string,
  onClose: PropTypes.func
};
