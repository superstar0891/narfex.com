import "./CabinetSettingsScreen.less";

import React from "react";
import { connect } from "react-redux";

import PageContainer from "../../../components/cabinet/PageContainerOld/PageContainerOld";
import { ProfileSidebarItem } from "../../../components/cabinet/ProfileSidebar/ProfileSidebar";
import CabinetBaseScreen from "../CabinetBaseScreen/CabinetBaseScreen";
import SettingSecurity from "./componets/SettingSecurity";
import SettingPersonal from "./componets/SettingPersonal";
import SettingKey from "./componets/SettingKey";
import LoadingStatus from "../../../components/cabinet/LoadingStatus/LoadingStatus";
import ProfileUser from "../../../components/cabinet/ProfileUser/ProfileUser";
import * as UI from "../../../../ui";
import * as utils from "../../../../utils";

import { ReactComponent as IdBadgeSvg } from "../../../../asset/24px/id-badge.svg";
import { ReactComponent as ShieldSvg } from "../../../../asset/24px/shield.svg";
import { ReactComponent as KeySvg } from "../../../../asset/24px/key.svg";
import * as actions from "../../../../actions";
import * as settingsActions from "../../../../actions/cabinet/settings";
import * as toastsActions from "../../../../actions/toasts";
import { Helmet } from "react-helmet";
import COMPANY from "../../../constants/company";

class CabinetSettingsScreen extends CabinetBaseScreen {
  get section() {
    return this.props.routerParams.section || "default";
  }

  get isLoading() {
    return !!this.props.loadingStatus[this.section];
  }

  componentDidMount() {
    this.props.setTitle(utils.getLang("cabinet_header_settings"));
    this.__load();
  }

  componentWillUpdate(nextProps) {
    if (nextProps.routerParams.section !== this.props.routerParams.section) {
      this.__load(nextProps.routerParams.section || "default");
    }
  }

  __load = (section = null) => {
    this.props.loadSettings();
  };

  render() {
    return (
      <div>
        <Helmet>
          <title>
            {[COMPANY.name, utils.getLang("cabinet_header_settings")].join(
              " - "
            )}
          </title>
        </Helmet>
        <PageContainer
          leftContent={
            !this.props.adaptive &&
            !this.isLoading &&
            this.__renderRightContent()
          }
          sidebarOptions={
            !this.props.adaptive && [
              <ProfileSidebarItem
                icon={<IdBadgeSvg />}
                label={utils.getLang("cabinet_settingsMenuPersonal")}
                baselink={true}
              />,
              <ProfileSidebarItem
                icon={<ShieldSvg />}
                label={utils.getLang("global_security")}
                section="security"
                active={this.props.routerParams.section === "security"}
              />,
              <ProfileSidebarItem
                icon={<KeySvg />}
                label={utils.getLang("cabinet_apiKey")}
                section="apikey"
                active={this.props.routerParams.section === "apikey"}
              />
              /*<ProfileSidebarItem
            icon={require('../../../asset/24px/user.svg')}
            label="Notifications"
            section="notifications"
            active={this.props.routerParams.section === 'notifications'}
          />*/
            ]
          }
        >
          {this.props.adaptive && <ProfileUser />}
          {this.__renderContent()}
        </PageContainer>
      </div>
    );
  }

  __renderContent = () => {
    if (this.isLoading) {
      return (
        <LoadingStatus
          status={this.props.loadingStatus[this.section]}
          onRetry={() => this.__load()}
        />
      );
    }

    switch (this.props.routerParams.section) {
      case "security": {
        return <SettingSecurity props={this.props} />;
      }
      case "notifications": {
        return this.__getNotificationsPageContent();
      }
      case "apikey": {
        return <SettingKey {...this.props} />;
      }
      default:
        return <SettingPersonal {...this.props} />;
    }
  };

  __getNotificationsPageContent = () => {
    return (
      <div>
        <UI.ContentBox className="CabinetSettingsScreen__padding_box">
          <div className="CabinetSettingsScreen__header withPadding">Вход</div>
          <div className="CabinetSettingsScreen__switch_item">
            <span className="text_span">Сообщить о входе</span>
            <span className="switch_span">
              <UI.Switch
                on={Math.random() >= 0.5}
                onChange={e => {
                  console.log(e);
                }}
              />
            </span>
          </div>
          <div className="CabinetSettingsScreen__switch_item">
            <span className="text_span">
              Сообщить о входе в аккаунт с нового IP
            </span>
            <span className="switch_span">
              <UI.Switch
                on={Math.random() >= 0.5}
                onChange={e => {
                  console.log(e);
                }}
              />
            </span>
          </div>
        </UI.ContentBox>
        <UI.ContentBox className="CabinetSettingsScreen__padding_box">
          <div className="CabinetSettingsScreen__header withPadding">
            Кошелек
          </div>
          <div className="CabinetSettingsScreen__switch_item">
            <span className="text_span">Поступление средств</span>
            <span className="switch_span">
              <UI.Switch
                on={Math.random() >= 0.5}
                onChange={e => {
                  console.log(e);
                }}
              />
            </span>
          </div>
          <div className="CabinetSettingsScreen__switch_item">
            <span className="text_span">
              Запрос на вывод средств с кошелька
            </span>
            <span className="switch_span">
              <UI.Switch
                on={Math.random() >= 0.5}
                onChange={e => {
                  console.log(e);
                }}
              />
            </span>
          </div>
          <div className="CabinetSettingsScreen__switch_item">
            <span className="text_span">Вывод средств с кошелька</span>
            <span className="switch_span">
              <UI.Switch
                on={Math.random() >= 0.5}
                onChange={e => {
                  console.log(e);
                }}
              />
            </span>
          </div>
        </UI.ContentBox>
        <UI.ContentBox className="CabinetSettingsScreen__padding_box">
          <div className="CabinetSettingsScreen__header withPadding">
            Инвестиции
          </div>
          <div className="CabinetSettingsScreen__switch_item">
            <span className="text_span">Открытие депозита</span>
            <span className="switch_span">
              <UI.Switch
                on={Math.random() >= 0.5}
                onChange={e => {
                  console.log(e);
                }}
              />
            </span>
          </div>
          <div className="CabinetSettingsScreen__switch_item">
            <span className="text_span">Запрос на вывод инвестиций</span>
            <span className="switch_span">
              <UI.Switch
                on={Math.random() >= 0.5}
                onChange={e => {
                  console.log(e);
                }}
              />
            </span>
          </div>
          <div className="CabinetSettingsScreen__switch_item">
            <span className="text_span">Подтерждение вывода инвестиций</span>
            <span className="switch_span">
              <UI.Switch
                on={Math.random() >= 0.5}
                onChange={e => {
                  console.log(e);
                }}
              />
            </span>
          </div>
          <div className="CabinetSettingsScreen__switch_item">
            <span className="text_span">Окончание депозита</span>
            <span className="switch_span">
              <UI.Switch
                on={Math.random() >= 0.5}
                onChange={e => {
                  console.log(e);
                }}
              />
            </span>
          </div>
          <div className="CabinetSettingsScreen__switch_item">
            <span className="text_span">Поступление дохода с депозита</span>
            <span className="switch_span">
              <UI.Switch
                on={Math.random() >= 0.5}
                onChange={e => {
                  console.log(e);
                }}
              />
            </span>
          </div>
        </UI.ContentBox>
      </div>
    );
  };

  __renderRightContent = () => {
    const headings = [
      <UI.TableColumn>
        {utils.getLang("cabinet_settingsAction")}
      </UI.TableColumn>,
      <UI.TableColumn>
        {utils.getLang("cabinet_settingsDevice")}
      </UI.TableColumn>,
      <UI.TableColumn>{utils.getLang("cabinet_settingsIP")}</UI.TableColumn>,
      <UI.TableColumn>{utils.getLang("cabinet_settingsDate")}</UI.TableColumn>
    ];

    const rows =
      this.props.user &&
      this.props.user.logs &&
      this.props.user.logs.map((item, i) => {
        return (
          <UI.TableCell key={i}>
            <UI.TableColumn>
              {utils.switchMatch(item.action, {
                auth_signin: utils.getLang("cabinet_settingsAuthSignIn"),
                default: item.action
              })}
            </UI.TableColumn>
            <UI.TableColumn style={{ width: 50 }}>
              {item.browser}
            </UI.TableColumn>
            <UI.TableColumn>{item.ip}</UI.TableColumn>
            <UI.TableColumn style={{ width: 140 }}>
              {utils.dateFormat(item.created_at, null).fromNow()}
            </UI.TableColumn>
          </UI.TableCell>
        );
      });

    return <UI.Table headings={headings}>{rows}</UI.Table>;
  };
}

export default connect(
  state => ({
    ...state.settings,
    profile: state.default.profile,
    adaptive: state.default.adaptive,
    translator: state.settings.translator,
    currentLang: state.default.currentLang
  }),
  {
    setTitle: actions.setTitle,
    loadSettings: settingsActions.loadSettings,
    setUserFieldValue: settingsActions.setUserFieldValue,
    toastPush: toastsActions.toastPush
  }
)(CabinetSettingsScreen);
