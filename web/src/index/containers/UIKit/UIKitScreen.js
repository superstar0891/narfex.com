import "./UIKit.less";

import React from "react";

import BaseScreen from "../BaseScreen";
import * as UI from "../../../ui/index";

export default class UIKitScreen extends BaseScreen {
  constructor(props) {
    super(props);

    this.state = {
      checkbox1: false,
      checkbox2: true,
      radio: "second",
      switch1: false,
      switch2: true
    };
  }

  render() {
    return (
      <div>
        <Section title="NumberFormat">
          <Line>
            <UI.NumberFormat number={8951.72348234123} />
          </Line>
          <Line>
            <UI.NumberFormat number={8951.72348234123} currency="btc" />
          </Line>
          <Line>
            <UI.NumberFormat
              number={8951.72348234123}
              type="up"
              indicator
              currency="btc"
            />
          </Line>
          <Line>
            <UI.NumberFormat
              number={8951.72348234123}
              type="down"
              indicator
              currency="btc"
            />
          </Line>
          <Line>
            <UI.NumberFormat
              number={8951.72348234123}
              skipTitle
              currency="btc"
            />
          </Line>
          <Line>
            <UI.NumberFormat
              number={8951.72348234123}
              fractionDigits={2}
              currency="usd"
            />
          </Line>
          <Line>
            <UI.NumberFormat
              number={8951.72348234123}
              fractionDigits={2}
              percent
            />
          </Line>
        </Section>
        <Section title="Buttons">
          <Line>
            <UI.Button>Button</UI.Button>
            <UI.Button rounded>Button</UI.Button>
            <UI.Button disabled>Button</UI.Button>
            <UI.Button type="secondary">Button</UI.Button>
            <UI.Button type="secondary">Button</UI.Button>
            <UI.Button rounded type="secondary">
              Button
            </UI.Button>
            <UI.Button type="negative">Button</UI.Button>
            <UI.Button type="negative_outline">Button</UI.Button>
            <UI.Button type="lite">Button</UI.Button>
          </Line>
          <Line>
            <UI.Button size="small">Button</UI.Button>
            <UI.Button rounded size="small">
              Button
            </UI.Button>
            <UI.Button size="small" disabled>
              Button
            </UI.Button>
            <UI.Button size="small" type="secondary">
              Button
            </UI.Button>
            <UI.Button size="small" type="secondary">
              Button
            </UI.Button>
            <UI.Button size="small" rounded type="secondary">
              Button
            </UI.Button>
            <UI.Button size="small" type="negative">
              Button
            </UI.Button>
            <UI.Button size="small" type="negative_outline">
              Button
            </UI.Button>
            <UI.Button size="small" type="lite">
              Button
            </UI.Button>
          </Line>
          <Line>
            <UI.Button size="small" currency="btc">
              Button
            </UI.Button>
            <UI.Button rounded size="small" currency="btc">
              Button
            </UI.Button>
            <UI.Button size="small" disabled currency="btc">
              Button
            </UI.Button>
            <UI.Button size="small" type="secondary" currency="btc">
              Button
            </UI.Button>
            <UI.Button size="small" type="secondary" currency="btc">
              Button
            </UI.Button>
            <UI.Button size="small" rounded type="secondary" currency="btc">
              Button
            </UI.Button>
            <UI.Button size="small" type="negative" currency="btc">
              Button
            </UI.Button>
            <UI.Button size="small" type="negative_outline" currency="btc">
              Button
            </UI.Button>
            <UI.Button size="small" type="lite" currency="btc">
              Button
            </UI.Button>
          </Line>
          <Line>
            <UI.Button size="small" currency="eth">
              Button
            </UI.Button>
            <UI.Button rounded size="small" currency="eth">
              Button
            </UI.Button>
            <UI.Button size="small" disabled currency="eth">
              Button
            </UI.Button>
            <UI.Button size="small" type="secondary" currency="eth">
              Button
            </UI.Button>
            <UI.Button size="small" type="secondary" currency="eth">
              Button
            </UI.Button>
            <UI.Button size="small" rounded type="secondary" currency="eth">
              Button
            </UI.Button>
            <UI.Button size="small" type="negative" currency="eth">
              Button
            </UI.Button>
            <UI.Button size="small" type="negative_outline" currency="eth">
              Button
            </UI.Button>
            <UI.Button size="small" type="lite" currency="eth">
              Button
            </UI.Button>
          </Line>
          <Line>
            <UI.Button size="small" currency="ltc">
              Button
            </UI.Button>
            <UI.Button rounded size="small" currency="ltc">
              Button
            </UI.Button>
            <UI.Button size="small" disabled currency="ltc">
              Button
            </UI.Button>
            <UI.Button size="small" type="secondary" currency="ltc">
              Button
            </UI.Button>
            <UI.Button size="small" type="secondary" currency="ltc">
              Button
            </UI.Button>
            <UI.Button size="small" rounded type="secondary" currency="ltc">
              Button
            </UI.Button>
            <UI.Button size="small" type="negative" currency="ltc">
              Button
            </UI.Button>
            <UI.Button size="small" type="negative_outline" currency="ltc">
              Button
            </UI.Button>
            <UI.Button size="small" type="lite" currency="ltc">
              Button
            </UI.Button>
          </Line>
        </Section>
        <Section title="FloatingButton">
          <Line style={{ paddingLeft: 150, paddingTop: 200 }} static>
            <UI.FloatingButton
              wrapper
              icon={require("../../../asset/24px/loop.svg")}
            >
              <UI.FloatingButtonItem
                icon={require("../../../asset/24px/loop.svg")}
                children="Send"
              />
              <UI.FloatingButtonItem
                icon={require("../../../asset/24px/loop.svg")}
                children="Transfers"
              />
              <UI.FloatingButtonItem
                icon={require("../../../asset/24px/loop.svg")}
                children="Create New Wallet"
              />
            </UI.FloatingButton>
          </Line>
        </Section>
        <Section title="Inputs">
          <Line style={{ width: 300 }}>
            <UI.Input placeholder="Placeholder" />
          </Line>
          <Line style={{ width: 300 }}>
            <UI.Input placeholder="Placeholder" multiLine />
          </Line>
          <Line style={{ width: 300 }}>
            <UI.Input type="password" placeholder="Password" />
          </Line>
          <Line style={{ width: 300 }}>
            <UI.Input
              onTextChange={console.log}
              type="number"
              placeholder="Number"
            />
          </Line>
          <Line style={{ width: 300 }}>
            <UI.Input
              onTextChange={console.log}
              cell
              type="number"
              placeholder="Number call"
            />
          </Line>
        </Section>
        <Section title="Inputs">
          <Line style={{ width: 300 }}>
            <UI.Dropdown
              placeholder="Placeholder"
              value={{ title: "BTC", note: "0.02112", value: "btc" }}
              onChange={console.log}
              options={[
                { title: "BTC", note: "0.02112", value: "btc" },
                { title: "ETH", note: "1.511", value: "eth" },
                { title: "LTC", note: "9.1002", value: "ltc" }
              ]}
            />
          </Line>
          <Line style={{ width: 300 }}>
            <UI.Dropdown
              placeholder="Placeholder"
              value="btc"
              onChange={console.log}
              options={[
                { title: "BTC", note: "0.02112", value: "btc" },
                { title: "ETH", note: "1.511", value: "eth" },
                { title: "LTC", note: "9.1002", value: "ltc" }
              ]}
            />
          </Line>
          <Line style={{ width: 200 }}>
            <UI.Dropdown
              size="small"
              placeholder="Placeholder"
              value="btc"
              onChange={console.log}
              options={[
                { title: "BTC", note: "0.02112", value: "btc" },
                { title: "ETH", note: "1.511", value: "eth" },
                { title: "LTC", note: "9.1002", value: "ltc" }
              ]}
            />
          </Line>
        </Section>
        <Section title="Range">
          <Line style={{ width: 300 }}>
            <UI.Range
              formatLabel={value => value + " Hours"}
              min={8}
              max={12}
              value={10}
              onChange={console.log}
              placeholder="Placeholder"
            />
          </Line>
        </Section>
        <Section title="Search">
          <Line style={{ width: 500 }}>
            <UI.Search placeholder="Search..." />
          </Line>
          <Line style={{ width: 500 }}>
            <UI.Search placeholder="Search..." lite />
          </Line>
        </Section>
        <Section title="Checkbox">
          <Line>
            <UI.CheckBox
              checked={this.state.checkbox1}
              onChange={() =>
                this.setState({ checkbox1: !this.state.checkbox1 })
              }
            >
              Checkbox
            </UI.CheckBox>
          </Line>
          <Line>
            <UI.CheckBox
              checked={this.state.checkbox2}
              onChange={() =>
                this.setState({ checkbox2: !this.state.checkbox2 })
              }
            >
              Checkbox
            </UI.CheckBox>
          </Line>
          <Line>
            <UI.CheckBox disabled>Checkbox</UI.CheckBox>
          </Line>
          <Line>
            <UI.CheckBox checked disabled>
              Checkbox
            </UI.CheckBox>
          </Line>
        </Section>
        <Section title="Radio">
          <Line>
            <UI.RadioGroup
              selected={this.state.radio}
              onChange={radio => this.setState({ radio })}
            >
              <UI.Radio value="first">Radio</UI.Radio>
              <UI.Radio value="second">Radio</UI.Radio>
              <UI.Radio value="last" disabled>
                Radio
              </UI.Radio>
            </UI.RadioGroup>
          </Line>
        </Section>
        <Section title="Switch">
          <Line>
            <UI.Switch
              on={this.state.switch1}
              onChange={() => this.setState({ switch1: !this.state.switch1 })}
            >
              Switch
            </UI.Switch>
          </Line>
          <Line>
            <UI.Switch
              on={this.state.switch2}
              onChange={() => this.setState({ switch2: !this.state.switch2 })}
            >
              Switch
            </UI.Switch>
          </Line>
          <Line>
            <UI.Switch disabled>Switch</UI.Switch>
          </Line>
          <Line>
            <UI.Switch on disabled>
              Switch
            </UI.Switch>
          </Line>
        </Section>
        <Section title="Messages" style={{ width: 500 }}>
          <Line>
            <UI.Message>Default</UI.Message>
          </Line>
          <Line>
            <UI.Message type="error">Error</UI.Message>
          </Line>
          <Line>
            <UI.Message type="warning">Warning</UI.Message>
          </Line>
          <Line>
            <UI.Message type="success">Success</UI.Message>
          </Line>
        </Section>
        <Section title="Alerts" style={{ width: 500 }}>
          <Line>
            <UI.Message alert>Default</UI.Message>
          </Line>
          <Line>
            <UI.Message type="error" alert>
              Error
            </UI.Message>
          </Line>
          <Line>
            <UI.Message type="warning" alert>
              Warning
            </UI.Message>
          </Line>
          <Line>
            <UI.Message type="success" alert>
              Success
            </UI.Message>
          </Line>
        </Section>
        <Section title="MarkDown" style={{ width: 500 }}>
          <Line>
            <UI.MarkDown
              content={`
            ## Header
            text
            text
            [link](#test) **Bold** *italic*
            `}
            />
          </Line>
        </Section>
        <Section>
          <Line>
            <UI.Toast type="fail" message="Toast Message" />
            <UI.Toast type="warning" message="Toast Message" />
            <UI.Toast type="success" message="Toast Message" />
            <UI.Toast type="info" message="Toast Message" />
            <UI.Toast
              type="fail"
              message="Long text Long text Long text Long text "
            />
          </Line>
        </Section>
      </div>
    );
  }
}

function Section(props) {
  return (
    <div className="UIKit__section" style={props.style}>
      <div className="UIKit__section__title">{props.title}</div>
      <div className="UIKit__section__cont">{props.children}</div>
    </div>
  );
}

function Line(props) {
  return (
    <div className="UIKit__section__line" style={props.style}>
      {props.children}
    </div>
  );
}
