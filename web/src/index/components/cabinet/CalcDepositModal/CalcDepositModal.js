import "./CalcDepositModal.less";

import React from "react";
import { connect } from "react-redux";
import SVG from "react-inlinesvg";

import * as UI from "../../../../ui/";
import * as utils from "../../../../utils";
import * as investmentsActions from "../../../../actions/cabinet/investments";
import * as actions from "../../../../actions";
import * as api from "../../../../services/api";
import apiSchema from "../../../../services/apiSchema";
import * as toastsActions from "../../../../actions/toasts";

const CalcDepositModal = class extends React.Component {
  state = {
    currencies: Object.keys(this.props.currencies)
      .map(actions.getCurrencyInfo)
      .filter(c => c.can_generate),
    plans: [],
    currency: "btc",
    amount: null,
    planId: null,
    planPercent: null,
    days: [],
    result: {},
    daysResult: [],
    maxDay: 0,
    errorAmountDay: null

    // TODO: Объединить days и daysResult
  };

  componentDidMount() {
    this.__handleChangeCurrency(this.state.currency);
  }

  __handleChangePlan = plan => {
    this.setState(
      {
        planId: plan.value,
        planPercent: plan.percent,
        maxDay: plan.days
      },
      this.__calculate
    );
  };

  __handleChangeCurrency = currency => {
    this.setState({ currency });
    api
      .call(apiSchema.Investment.PlansGet, {
        currency,
        deposit_type: "dynamic"
      })
      .then(({ plans }) => {
        this.setState(
          {
            plans: plans.map(p => ({
              title: p.dynamic.description,
              value: p.dynamic.id,
              percent: p.dynamic.percent,
              days: p.dynamic.days,
              note: `${p.dynamic.percent}% ${p.dynamic.days} ${utils.getLang(
                "global_days",
                true
              )}`
            })),
            planId: plans[0].dynamic.id,
            maxDay: plans[0].dynamic.days,
            planPercent: plans[0].dynamic.percent
          },
          this.__calculate
        );
      });
  };

  __handleChangeDay = (dayId, options) => {
    this.setState(
      {
        days: this.state.days.map((day, id) => {
          if (dayId === id) {
            return { ...day, ...options };
          }
          return day;
        })
      },
      this.__calculateThrottled
    );
  };

  __daysIsFilled(allField = false) {
    return (
      !!this.state.days.length &&
      this.state.amount &&
      this.state.days.every(d => !!d.dayNumber && (!allField || !!d.amount))
    );
  }

  __calculate() {
    if (!this.__daysIsFilled()) {
      this.setState({
        daysResult: [],
        result: []
      });
      return false;
    }

    this.setState({ errorAmountDay: null });

    investmentsActions
      .calculate({
        currency: this.state.currency,
        planId: this.state.planId,
        amount: this.state.amount,
        days: this.state.days
      })
      .then(result => {
        this.setState({
          result: {
            percent: result.percent,
            profit: result.profit
          },
          daysResult: result.profits_result
        });
      })
      .catch(err => {
        toastsActions.error(err.message);
        if (err.code === "amount_incorrect") {
          this.setState({ errorAmountDay: err.day });
        }
      });
  }

  __calculateThrottled = utils.throttle(this.__calculate.bind(this), 500);

  __handleDeleteDay = dayId => {
    this.setState(
      {
        days: this.state.days.filter((day, id) => dayId !== id),
        daysResult: this.state.daysResult.filter((day, id) => dayId !== id)
      },
      this.__calculate
    );
  };

  __handleAddDay = () => {
    this.setState({
      days: [...this.state.days, { dayNumber: null, amount: null }]
    });
  };

  __renderProfit = (title, profit, percent) => {
    if (!profit || !percent) return null;
    return (
      <div className="CalcDepositModal__description">
        <div className="CalcDepositModal__description__label">{title}:</div>
        <div className="CalcDepositModal__description__value">
          {utils.formatDouble(profit)} {this.state.currency.toUpperCase()}{" "}
          <UI.NumberFormat
            brackets
            fractionDigits={2}
            number={percent}
            type="auto"
            percent
          />
        </div>
      </div>
    );
  };

  render() {
    const { currencies } = this.state;
    const currencyInfo = actions.getCurrencyInfo(this.state.currency);

    return (
      <UI.Modal
        className="CalcDepositModal__wrapper"
        isOpen={true}
        onClose={this.props.onClose}
      >
        <UI.ModalHeader>{utils.getLang("cabinet_depositCalc")}</UI.ModalHeader>
        <UI.CircleIcon
          className="CalcDepositModal__icon"
          currency={currencyInfo}
        />
        <div className="CalcDepositModal">
          <div className="CalcDepositModal__row">
            <div className="CalcDepositModal__column">
              <UI.Dropdown
                options={currencies.map(c => ({
                  title: utils.ucfirst(c.name),
                  value: c.abbr
                }))}
                value={this.state.currency}
                onChange={c => this.__handleChangeCurrency(c.value)}
              />
            </div>
            <div className="CalcDepositModal__column">
              <UI.Dropdown
                onChange={p => this.__handleChangePlan(p)}
                options={this.state.plans}
                value={this.state.planId}
              />
            </div>
          </div>
          <div className="CalcDepositModal__row">
            <div className="CalcDepositModal__column">
              <UI.Input
                placeholder={utils.getLang("cabinet_investmentAmount", true)}
                value={this.state.amount}
                onTextChange={value => {
                  this.setState({ amount: value }, this.__calculate);
                }}
                indicatorWidth={40}
                indicator={this.state.currency.toUpperCase()}
              />
            </div>
            <div className="CalcDepositModal__column">
              {this.__renderProfit(
                utils.getLang("cabinet_IncomeWithoutConclusions"),
                (this.state.amount / 100) * this.state.planPercent,
                this.state.planPercent
              )}
            </div>
          </div>

          {!!this.state.days.length && (
            <div className="CalcDepositModal__delimiter" />
          )}

          <div className="CalcDepositModal__days">
            {this.state.days.map((day, dayId) => {
              const disabled = this.state.days.length > dayId + 1;
              return (
                <div className="CalcDepositModal__day">
                  <div className="CalcDepositModal__row ">
                    <div className="CalcDepositModal__column">
                      <UI.Input
                        onTextChange={dayNumber =>
                          this.__handleChangeDay(dayId, { dayNumber })
                        }
                        placeholder={utils.getLang("global_day", true)}
                        indicatorWidth={50}
                        disabled={disabled}
                        value={day.dayNumber}
                        error={
                          day.dayNumber > this.state.maxDay ||
                          (day.amount && !day.dayNumber) ||
                          (day.dayNumber &&
                            dayId &&
                            parseInt(day.dayNumber) <=
                              parseInt(this.state.days[dayId - 1].dayNumber))
                        }
                        indicator={day.dayNumber && utils.getLang("global_day")}
                      />
                    </div>
                    <div className="CalcDepositModal__column">
                      {this.state.daysResult[dayId] &&
                        this.__renderProfit(
                          `${utils.getLang("cabinet_incomeDay_prefix")} ${
                            day.dayNumber
                          } ${utils.getLang("cabinet_incomeDay_postfix")}:`,
                          this.state.daysResult[dayId].profit,
                          this.state.daysResult[dayId].percent
                        )}
                    </div>
                    <div className="CalcDepositModal__column CalcDepositModal__drop_button">
                      <UI.Button
                        onClick={() => this.__handleDeleteDay(dayId)}
                        currency={this.state.currency}
                        type="secondary"
                      >
                        <SVG
                          src={require("../../../../asset/24px/trash.svg")}
                        />
                      </UI.Button>
                    </div>
                  </div>
                  <div className="CalcDepositModal__row">
                    <div className="CalcDepositModal__column amount">
                      <UI.Input
                        placeholder={utils.getLang("global_withdraw", true)}
                        indicatorWidth={40}
                        disabled={!day.dayNumber || disabled}
                        onTextChange={amount =>
                          this.__handleChangeDay(dayId, { amount })
                        }
                        value={
                          (day.amount || "")
                            .toString()
                            .split(".")
                            .pop().length <= 8
                            ? day.amount
                            : utils.formatDouble(day.amount, 8)
                        }
                        error={
                          this.state.errorAmountDay === parseInt(day.dayNumber)
                        }
                        indicator={this.state.currency.toUpperCase()}
                      />
                      <UI.Button
                        currency={this.state.currency}
                        type="secondary"
                        disabled={!this.state.daysResult[dayId] || disabled}
                        onClick={() => {
                          this.__handleChangeDay(dayId, {
                            amount: this.state.daysResult[dayId].profit
                          });
                        }}
                      >
                        {utils.getLang("cabinet_sendCoinsModal_max")}
                      </UI.Button>
                    </div>
                    <div className="CalcDepositModal__column">
                      {this.state.daysResult[dayId] &&
                        this.__renderProfit(
                          utils.getLang(
                            "global_decreaseInPercentageOfWithdrawal"
                          ),
                          this.state.daysResult[dayId].drop_amount,
                          this.state.daysResult[dayId].drop_percent
                        )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

          <div className="CalcDepositModal__delimiter" />

          <div className="CalcDepositModal__row CalcDepositModal__result">
            <div className="CalcDepositModal__column">
              <UI.Button
                disabled={
                  !this.state.amount ||
                  (!!this.state.days.length && !this.__daysIsFilled(true))
                }
                onClick={this.__handleAddDay}
                currency={this.state.currency}
              >
                {utils.getLang("cabinet_addNewDay")}
              </UI.Button>
            </div>
            <div className="CalcDepositModal__column">
              {this.__renderProfit(
                utils.getLang("cabinet_finalProfit"),
                this.state.result.profit,
                this.state.result.percent
              )}
            </div>
          </div>
        </div>
      </UI.Modal>
    );
  }
};

export default connect(state => ({
  currencies: state.cabinet.currencies
}))(CalcDepositModal);
