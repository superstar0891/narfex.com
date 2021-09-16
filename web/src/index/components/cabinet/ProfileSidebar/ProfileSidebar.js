import "./ProfileSidebar.less";

import React from "react";
import PropTypes from "prop-types";
import SVG from "react-inlinesvg";
import { BaseLink } from "react-router5";
import { connect } from "react-redux";

import { classNames, makeModalParams } from "../../../../utils";
import router from "../../../../router";
import Footer from "../Footer/Footer";
import ProfileUser from "../ProfileUser/ProfileUser";

class ProfileSidebar extends React.Component {
  render() {
    // let verified = false; //verificationText = 'Not verified';
    //
    // switch (this.props.verification) {
    //   case 'verified':
    //     verified = true;
    //     //verificationText = 'Verified';
    //     break;
    //   default: break;
    // }

    return (
      <div className="ProfileSidebar">
        <div className="ProfileSidebar__top">
          <ProfileUser />
          <div className="ProfileSidebar__menu">
            {this.__getBackButton()}
            {this.props.sidebarOptions &&
              this.props.sidebarOptions.map(child => {
                if (!React.isValidElement(child)) {
                  return child;
                }
                return React.cloneElement(child, {
                  isActive:
                    this.section === child.props.section &&
                    !!child.props.section,
                  key: Math.random()
                });
              })}
          </div>
        </div>
        <Footer className="ProfileSidebar__footer" />
      </div>
    );
  }

  __getBackButton = () => {
    if (!this.section || !this.appName) {
      return "";
    }

    const routeName = this.section
      ? window.location.pathname.substr(1)
      : "profile";

    return (
      <BaseLink
        router={router}
        routeName={routeName}
        className="ProfileSidebar__menu__item ProfileSidebar__menu__item_passive"
        activeClassName="_a"
      >
        <SVG src={require("../../../../asset/cabinet/angle_left.svg")} />
        {this.section ? this.appName : "Profile"}
      </BaseLink>
    );
  };
}

ProfileSidebar.defaultProps = {
  appName: null,
  section: null,
  role: "",
  verification: "",
  user: {
    photo_url: "",
    first_name: "",
    last_name: ""
  }
};

// ProfileSidebar({ count, children, items, section = null, appName = null }) {
//   const getBackButton = () => {
//     if (!section || !appName) {
//       return '';
//     }
//     const routeName = section ? window.location.pathname.substr(1) : 'dashboard';
//     return (
//       <BaseLink
//         router={router}
//         routeName={routeName}
//         className="ProfileSidebar__menu__item ProfileSidebar__menu__item_passive"
//         activeClassName="_a"
//       >
//         <SVG src={require('../../../asset/cabinet/angle_left.svg')} />
//         {section ? appName : 'Dashboard'}
//       </BaseLink>
//     )
//   };
//
//   return (
//     <div className="ProfileSidebar">
//       <div className="ProfileSidebar__user">
//         <div className="ProfileSidebar__user__avatar__wrap">
//           <img className="ProfileSidebar__user__avatar blur" src="https://images.unsplash.com/photo-1496671431883-c102df9ae8f9?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=2253&q=80" alt="" />
//           <img className="ProfileSidebar__user__avatar" src="https://images.unsplash.com/photo-1496671431883-c102df9ae8f9?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=2253&q=80" alt="" />
//         </div>
//         <h3 className="ProfileSidebar__user__title">Bitcoin bot</h3>
//         <p className="ProfileSidebar__user__txt">BTCBOT</p>
//         <p className="ProfileSidebar__user__txt">Agent</p>
//         <button className="ProfileSidebar__user__verify">Verify</button>
//       </div>
//
//       <div className="ProfileSidebar__menu">
//         {getBackButton()}
//         {React.Children.map(items, (child) => {
//           if (!React.isValidElement(child)) {
//             return child;
//           }
//
//           return React.cloneElement(child, {
//             isActive: section === child.props.section && !!child.props.section
//           });
//         })}
//       </div>
//     </div>
//   )
// }

ProfileSidebar.propTypes = {
  items: PropTypes.node
};

export function ProfileSidebarItem({
  icon = null,
  label,
  onClick,
  section,
  modal,
  baselink,
  active,
  hide = false
}) {
  const isLink = section || modal || baselink;
  const Component = isLink ? BaseLink : "div";
  const Icon = () => icon;

  let params = {};

  if (isLink) {
    params.routeName = router.getState().name;
    params.router = router;
    params.activeClassName = "active";
    params.ignoreQueryParams = false;
    params.activeStrict = true;
  }

  if (section) {
    params.routeParams = { section };
  } else if (modal) {
    params.routeParams = makeModalParams(modal);
  }

  if (hide) {
    return null;
  }

  return (
    <Component
      className={classNames({
        ProfileSidebar__menu__item: true
      })}
      onClick={onClick}
      {...params}
    >
      <Icon />
      {label}
    </Component>
  );
}

ProfileSidebarItem.propTypes = {
  icon: PropTypes.object,
  label: PropTypes.string,
  onClick: PropTypes.func,
  active: PropTypes.bool,
  section: PropTypes.string,
  modal: PropTypes.string,
  baselink: PropTypes.bool
};

export default connect(state => ({
  profile: state.default.profile
}))(ProfileSidebar);
