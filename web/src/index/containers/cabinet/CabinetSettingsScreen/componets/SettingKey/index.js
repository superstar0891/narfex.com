import "./SettingKey.less";
import React from "react";
import { connect } from "react-redux";
import SVG from "react-inlinesvg";
import copyText from "clipboard-copy";

import * as settingsActions from "src/actions/cabinet/settings";
import * as utils from "src/utils";
import LoadingStatus from "src/index/components/cabinet/LoadingStatus/LoadingStatus";
import EmptyContentBlock from "src/index/components/cabinet/EmptyContentBlock/EmptyContentBlock";

import ContentBox from "src/ui/components/ContentBox/ContentBox";
import * as UI from "src/ui";
import * as actions from "src/actions";
import * as toasts from "src/actions/toasts";

class SettingKey extends React.Component {
  state = {
    apiKeyName: null
  };

  componentDidMount() {
    const { dataApiKeys } = this.props;
    if (!dataApiKeys) {
      this.__handleCheckData();
    } else {
      this._handleIsSecretKey();
    }
  }

  __handleCheckData = () => {
    settingsActions.getApiKeys();
  };

  _handleIsSecretKey = () => {
    settingsActions.isSecretKey();
  };

  __handleCreateKey = () => {
    const { apiKeyName } = this.state;
    if (!apiKeyName) {
      toasts.error(utils.getLang("cabinet__requiredApiName"));
      return false;
    }

    actions.gaCode().then(code => {
      settingsActions
        .createKey({
          name: apiKeyName,
          ga_code: code
        })
        .then(() => {
          toasts.success(utils.getLang("cabinet__successCreateKey"));
          this.setState({ apiKeyName: "" });
        })
        .catch(err => {
          toasts.error(err.message);
        });
    });
  };

  __handleDeleteApiKey = key_id => {
    if (!key_id) {
      return false;
    }
    actions.gaCode().then(code => {
      settingsActions
        .deleteKey({
          key_id,
          ga_code: code
        })
        .then(() => {
          toasts.success(utils.getLang("cabinet__succesDeleteKey"));
          this.__handleCheckData();
        })
        .catch(err => {
          toasts.error(err.message);
        });
    });
  };

  __handleGetSecretKey = key_id => {
    if (!key_id) {
      return false;
    }
    actions.gaCode().then(code => {
      settingsActions
        .getSecretKey({
          key_id,
          ga_code: code
        })
        .then(item => {})
        .catch(err => {
          toasts.error(err.message);
        });
    });
  };

  __handleSaveItem = item => {
    if (!item.id) {
      return false;
    }
    if (Array.isArray(item.allow_ips) && item.allow_ips) {
      const filter = item.allow_ips.filter(item => item.address === "");
      if (filter.length > 0) {
        toasts.error(utils.getLang("cabinet_ip_address_empty"));
        return false;
      }
    }
    actions.gaCode().then(code => {
      settingsActions
        .saveItemKey({
          key_id: item.id,
          allow_ips: item.allow_ips.map(item => item.address).join(", "),
          name: item.name,
          permission_trading: item.permission_trading,
          permission_withdraw: item.permission_withdraw,
          ga_code: code
        })
        .then(item => {
          toasts.success(utils.getLang("cabinet_satting_save_key"));
        })
        .catch(err => {
          toasts.error(err.message);
        });
    });
  };

  __handleSettingIpAccess = (id, radio) => {
    settingsActions.settingIpAccess(id, radio);
  };

  __handleAddIpAddress = id => {
    settingsActions.addIpAddress(id);
  };

  __handleIpFieldValue = (key_id, id_ip, value) => {
    settingsActions.setIpAddressFieldValue(key_id, id_ip, value);
  };

  __handleDeleteIpAddress = ({ key_id, id_ip }) => {
    settingsActions.deleteIpAddress(key_id, id_ip);
  };

  __handleSettingsCheckTrading = (id, permission_trading) => {
    settingsActions.settingsCheckTrading(id, permission_trading);
  };

  __handleSettingsCheckWithdraw = (id, permission_withdraw) => {
    settingsActions.settingsCheckWithdraw(id, permission_withdraw);
  };

  __copy = public_key => {
    copyText(public_key).then(() => {
      this.props.toastPush(
        utils.getLang("cabinet_ keyCopiedSuccessfully"),
        "success"
      );
    });
  };

  __renderListApiKeys = () => {
    const { dataApiKeys } = this.props;
    if (!dataApiKeys) {
      return <LoadingStatus inline status="loading" />;
    }
    if (dataApiKeys.length === 0) {
      return (
        <EmptyContentBlock
          icon={require("asset/120/noApiKey.svg")}
          message={utils.getLang("cabinet__noApiKey")}
        />
      );
    }

    const closeEyeSvg = require("asset/16px/eye-closed.svg");
    const openEyeSvg = require("asset/16px/eye-open.svg");
    const copySvg = require("asset/16px/copy.svg");
    const plusSvg = require("asset/16px/plus.svg");
    const basketSvg = require("asset/24px/basket.svg");

    const listApiKeys = dataApiKeys.map((item, i) => {
      const ip_recomended = !item.radioCheck ? "first" : item.radioCheck;
      return (
        <ContentBox className="ApiKey__Item" key={i}>
          <div className="ApiKey__block">
            <div className="ApiKey__title">{item.name}</div>
            <div className="ApiKey__buttons">
              <UI.Button
                size="small"
                type="secondary"
                onClick={() => {
                  this.__handleDeleteApiKey(item.id);
                }}
              >
                {utils.getLang("cabinet__deleteKey")}
              </UI.Button>
            </div>
          </div>
          <div className="ApiKey__information">
            <div
              className="ApiKey__key"
              onClick={() => {
                this.__copy(item.public_key);
              }}
            >
              <div className="ApiKey__information-title">
                <span className="ApiKey__svg">
                  <SVG src={copySvg} />
                </span>
                {utils.getLang("cabinet_apiKey")}:
              </div>
              <div className="ApiKey__text">{item.public_key}</div>
            </div>
            <div
              className="ApiKey__secret"
              onClick={() => {
                item.displaySecretKey
                  ? this.__copy(item.secret_key)
                  : this.__handleGetSecretKey(item.id);
              }}
            >
              <div className="ApiKey__information-title">
                <span className="ApiKey__svg">
                  <SVG src={item.displaySecretKey ? openEyeSvg : closeEyeSvg} />
                </span>
                {utils.getLang("cabinet_secretKey")}:
              </div>
              <div className="ApiKey__text">
                {item.secret_key || "*".repeat(30)}
              </div>
            </div>
            <div className="ApiKey__restrictions">
              <div className="ApiKey__information-title">
                {utils.getLang("cabinet__restrictionsAPI")}:
              </div>
              <UI.CheckBox checked disabled>
                {utils.getLang("read")}
              </UI.CheckBox>
              <UI.CheckBox
                checked={item.permission_trading}
                onChange={() => {
                  this.__handleSettingsCheckTrading(
                    item.id,
                    item.permission_trading
                  );
                }}
              >
                {utils.getLang("enable_trading")}
              </UI.CheckBox>
              <UI.CheckBox
                checked={item.permission_withdraw}
                onChange={() => {
                  this.__handleSettingsCheckWithdraw(
                    item.id,
                    item.permission_withdraw
                  );
                }}
              >
                {utils.getLang("enable_withdrawals")}
              </UI.CheckBox>
            </div>
            <div className="ApiKey__ipAddress">
              <div className="ApiKey__information-title">
                {utils.getLang("ip_access_restrictions:")}:
              </div>
              <UI.RadioGroup
                selected={ip_recomended}
                onChange={radio => this.__handleSettingIpAccess(item.id, radio)}
              >
                <UI.Radio value="first">
                  {utils.getLang("unrestricted_ip")} <br />{" "}
                  <span>{utils.getLang("unrestricted_ip_warning")}</span>
                </UI.Radio>
                <UI.Radio value="second">
                  {utils.getLang("unrestricted_ip_recommended")}
                </UI.Radio>
              </UI.RadioGroup>
              {item.addIpAddress && item.allow_ips && (
                <div className="ApiKey__ipAddAddress">
                  {item.allow_ips.map((data, i) => {
                    return (
                      <UI.Input
                        indicator={
                          <div
                            className="svg_basket"
                            onClick={() =>
                              this.__handleDeleteIpAddress({
                                key_id: item.id,
                                id_ip: i
                              })
                            }
                          >
                            <SVG src={basketSvg} />
                          </div>
                        }
                        onTextChange={value =>
                          this.__handleIpFieldValue({
                            key_id: item.id,
                            id_ip: i,
                            value
                          })
                        }
                        placeholder={utils.getLang("trusted_ip_address", true)}
                        autoFocus={true}
                        error={data.address === "" && data.touched}
                        value={data.address}
                        key={i}
                      />
                    );
                  })}
                  <UI.Button
                    size="middle"
                    onClick={() => {
                      this.__handleAddIpAddress(item.id);
                    }}
                  >
                    <SVG src={plusSvg} />
                  </UI.Button>
                </div>
              )}
            </div>
            <div className="ApiKey__saveButton">
              <UI.Button
                size="large"
                onClick={() => {
                  this.__handleSaveItem(item);
                }}
                disabled={!item.canSave}
              >
                {utils.getLang("cabinet_settingsSave")}
              </UI.Button>
            </div>
          </div>
        </ContentBox>
      );
    });

    return listApiKeys;
  };

  render() {
    const { apiKeyName } = this.state;
    return (
      <>
        <ContentBox className="ApiKey">
          <div className="ApiKey__title">
            {utils.getLang("cabinet__newCreateKey")}
          </div>
          <div className="ApiCreateKey__form">
            <UI.Input
              placeholder={utils.getLang("cabinet__apiKeyName", true)}
              onTextChange={value => this.setState({ apiKeyName: value })}
              autoFocus={true}
              value={apiKeyName}
            />
            <UI.Button size="large" onClick={this.__handleCreateKey}>
              {utils.getLang("site__walletCreateBtn")}
            </UI.Button>
          </div>
        </ContentBox>
        {this.__renderListApiKeys()}
      </>
    );
  }
}

export default connect(state => ({
  dataApiKeys: state.settings.dataApiKeys
}))(SettingKey);
