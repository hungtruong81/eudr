import BaseSheet from "@/components/shared/base-sheet";
import React, { useCallback, useEffect, useState } from "react";
import { IHarvestPlan, ISchedule } from "../types";
import {
  Button,
  Col,
  DatePicker,
  Form,
  InputNumber,
  Row,
  Select,
  TimePicker,
  Typography,
  message,
} from "antd";
import { PlusOutlined, MinusCircleOutlined } from "@ant-design/icons";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getContracts } from "@/lib/api";
import { IContract } from "@/lib/types";
import { IPlotByTransactionTicket } from "@/features/manage-land/land/types";
import { getLandByTransactionTicket } from "@/features/manage-land/land/actions";
import dayjs from "dayjs";
import {
  createPlan,
  createSchedule,
  getSchedules,
  updatePlan,
  updateSchedule,
} from "../actions";
import { handleApiError } from "@/lib/api-error";
import { useQuery } from "@tanstack/react-query";
import { useTranslations } from "next-intl";

interface IPlanSheetProps {
  open: boolean;
  onClose: () => void;
  plan: IHarvestPlan | null;
  onRefresh?: () => void;
}

const PlanSheet = ({ open, onClose, plan, onRefresh }: IPlanSheetProps) => {
  const t = useTranslations("Harvest.Plan");
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [ticketCode, setTicketCode] = useState<string | undefined>();

  const tappingRegime = Form.useWatch("tapping_regime", form);
  const expectedYield = Form.useWatch("expected_yield", form);
  const harvestSchedules = Form.useWatch("harvest_schedules", form);

  const { data: scheduleData } = useQuery({
    queryKey: ["harvest-schedules", plan?.harvest_plan_code],
    queryFn: () =>
      getSchedules({
        page: 1,
        limit: 100,
        harvest_plan_code: plan?.harvest_plan_code,
      }),
    enabled: !!plan?.harvest_plan_code,
  });

  useEffect(() => {
    if (plan && open) {
      form.setFieldsValue({
        contract_code: plan.contract_code,
        expected_yield: Number(plan.expected_yield),
        tapping_regime: plan.tapping_regime,
        harvest_start_date: dayjs(plan.harvest_start_date),
        harvest_end_date: dayjs(plan.harvest_end_date),
        plot_ids: plan.lands?.map((l) => String(l.plot_id)) || [],
      });
    } else if (!open) {
      form.resetFields();
      setTicketCode(undefined);
    }
  }, [plan, open, form]);

  useEffect(() => {
    const records = (scheduleData?.data?.records ||
      scheduleData?.data) as ISchedule[];
    if (plan && records) {
      const formattedSchedules = records.map((sch: ISchedule) => ({
        plot_id: String(sch.plot_id),
        pickup_date: dayjs(sch.pickup_date),
        pickup_time: dayjs(sch.pickup_time, "HH:mm:ss"),
        expected_yield: Number(sch.expected_yield),
      }));

      form.setFieldValue("harvest_schedules", formattedSchedules);

      if (formattedSchedules.length > 0 && !form.getFieldValue("pickup_time")) {
        form.setFieldValue("pickup_time", formattedSchedules[0].pickup_time);
      }
    }
  }, [scheduleData, plan, form]);

  const mapContractOptions = useCallback(
    (contract: IContract) => ({
      label: contract.contract_code,
      value: contract.contract_code,
      ticket_code: contract.transaction_ticket_code,
      latex_weight: contract.latex_weight || 0,
      scrap_rubber_weight: contract.scrap_rubber_weight || 0,
    }),
    [],
  );

  const mapLandOptions = useCallback(
    (land: IPlotByTransactionTicket) => ({
      label: land.plot_name,
      value: String(land.plot_id),
    }),
    [],
  );

  const handleContractChange = async (val: any, option: any) => {
    const newTicketCode = option?.ticket_code;
    setTicketCode(newTicketCode);
    form.setFieldValue("plot_ids", []);

    if (option) {
      const totalWeight =
        +(option.latex_weight || 0) + +(option.scrap_rubber_weight || 0);
      form.setFieldValue("expected_yield", totalWeight);
    }

    if (newTicketCode) {
      try {
        const res = await getLandByTransactionTicket({
          transaction_ticket_code: newTicketCode,
          page: 1,
          limit: 100,
        });
        const records = res?.data?.records || (res as any)?.records || [];
        if (records.length > 0) {
          const allPlotIds = records.map((land: any) => String(land.plot_id));
          form.setFieldValue("plot_ids", allPlotIds);
        }
      } catch (error) {
        console.error("Lỗi khi auto-select lô đất:", error);
      }
    }
  };

  const handleGeneratePlan = () => {
    form
      .validateFields([
        "harvest_start_date",
        "harvest_end_date",
        "tapping_regime",
        "pickup_time",
        "expected_yield",
      ])
      .then((values) => {
        const {
          harvest_start_date,
          harvest_end_date,
          tapping_regime,
          pickup_time,
          expected_yield,
        } = values;

        let harvestDates: dayjs.Dayjs[] = [];
        const startDate = dayjs(harvest_start_date);
        const endDate = dayjs(harvest_end_date);

        if (tapping_regime !== "F") {
          const stepDays = parseInt(tapping_regime.replace("D", "")) || 1;
          let currentDate = startDate;
          while (
            currentDate.isBefore(endDate) ||
            currentDate.isSame(endDate, "day")
          ) {
            harvestDates.push(currentDate);
            currentDate = currentDate.add(stepDays, "day");
          }
        } else {
          harvestDates.push(startDate);
        }

        const numberOfHarvests = harvestDates.length;
        const yieldPerHarvest =
          numberOfHarvests > 0
            ? Number((expected_yield / numberOfHarvests).toFixed(2))
            : 0;

        const generatedSchedules = harvestDates.map((date) => ({
          pickup_date: date,
          pickup_time: pickup_time,
          expected_yield: yieldPerHarvest,
          plot_id: form.getFieldValue("plot_ids")?.[0] || undefined,
        }));

        form.setFieldValue("harvest_schedules", generatedSchedules);
      })
      .catch((info) => {
        console.log("Validate Failed:", info);
      });
  };

  useEffect(() => {
    if (harvestSchedules && harvestSchedules.length > 0 && expectedYield) {
      const numberOfHarvests = harvestSchedules.length;
      const yieldPerHarvest = Number(
        (expectedYield / numberOfHarvests).toFixed(2),
      );

      const updatedSchedules = harvestSchedules.map((schedule: any) => ({
        ...schedule,
        expected_yield: yieldPerHarvest,
      }));

      if (harvestSchedules[0]?.expected_yield !== yieldPerHarvest) {
        form.setFieldValue("harvest_schedules", updatedSchedules);
      }
    }
  }, [harvestSchedules?.length, expectedYield, form]);

  const handleSubmit = async (values: any) => {
    try {
      setLoading(true);

      const planPayload = {
        harvest_start_date: dayjs(values.harvest_start_date).format(
          "YYYY-MM-DD",
        ),
        harvest_end_date: dayjs(values.harvest_end_date).format("YYYY-MM-DD"),
        tapping_regime: values.tapping_regime,
        contract_code: values.contract_code,
        plot_ids: values.plot_ids.map(Number),
        expected_yield: String(values.expected_yield),
        notes: values.notes || "",
      };

      let harvestPlanCode = plan?.harvest_plan_code;

      if (plan) {
        await updatePlan(planPayload, plan.harvest_plan_code);
      } else {
        const planResponse = await createPlan(planPayload);
        harvestPlanCode =
          planResponse?.harvest_plan?.harvest_plan_code ||
          planResponse?.harvest_plan_code;

        if (!harvestPlanCode) {
          throw new Error(
            t("loading_plan"),
          );
        }
      }

      const schedules = (values.harvest_schedules || []).map(
        (schedule: any) => ({
          plot_id: Number(schedule.plot_id),
          pickup_date: dayjs(schedule.pickup_date).format("YYYY-MM-DD"),
          pickup_time: dayjs(schedule.pickup_time).format("HH:mm"),
          expected_yield: Number(schedule.expected_yield),
        }),
      );

      const schedulePayload = {
        harvest_plan_code: harvestPlanCode as string,
        schedules: schedules,
      };

      if (plan) {
        await updateSchedule(schedulePayload);
        message.success(t("update_success"));
        onRefresh?.();
      } else {
        await createSchedule(schedulePayload);
        message.success(t("create_success"));
        onRefresh?.();
      }

      form.resetFields();
      onClose();
    } catch (error: any) {
      handleApiError(error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={form.submit}
      loading={loading}
      title={plan ? t("edit_plan") : t("add_plan")}>
      <Form form={form} layout="vertical" onFinish={handleSubmit}>
        <Row gutter={16}>
          <Col span={12}>
            <Form.Item
              name="contract_code"
              label={t("contract_code")}
              rules={[
                { required: true, message: t("select_contract_msg") },
              ]}>
              <InfiniteScrollSelect<IContract>
                queryKey={["contracts-select"]}
                fetchFn={getContracts}
                mapOption={mapContractOptions}
                allowClear
                placeholder={t("select_contract")}
                onChange={handleContractChange}
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="plot_ids"
              label={t("plot_list_by_contract")}
              rules={[{ required: true, message: t("select_plot_msg") }]}>
              <InfiniteScrollSelect<IPlotByTransactionTicket>
                queryKey={["lands-transaction-select", ticketCode || ""]}
                fetchFn={(params) =>
                  getLandByTransactionTicket({
                    ...params,
                    limit: 100,
                    transaction_ticket_code: ticketCode!,
                  })
                }
                mapOption={mapLandOptions}
                allowClear
                mode="multiple"
                disabled
                placeholder={t("select_contract")}
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="expected_yield"
              label={t("yield_kg")}
              rules={[
                { required: true, message: t("enter_expected_yield_msg") },
              ]}>
              <InputNumber style={{ width: "100%" }} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="tapping_regime"
              label={t("tapping_regime_label")}
              rules={[{ required: true, message: t("select_tapping_regime_msg") }]}>
              <Select
                options={[
                  { label: "D1", value: "D1" },
                  { label: "D2", value: "D2" },
                  { label: "D3", value: "D3" },
                  { label: "D4", value: "D4" },
                  { label: "D5", value: "D5" },
                  { label: "D6", value: "D6" },
                  { label: "Flexible", value: "F" },
                ]}
                placeholder={t("select_tapping_regime")}
              />
            </Form.Item>
          </Col>

          <Col span={8}>
            <Form.Item
              name="harvest_start_date"
              label={t("start_date")}
              rules={[
                { required: true, message: t("select_start_date_msg") },
              ]}>
              <DatePicker
                style={{ width: "100%" }}
                format="DD/MM/YYYY"
                disabledDate={(current) => current.isBefore(dayjs(), "day")}
                placeholder={t("select_start_date_msg")}
              />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item
              name="harvest_end_date"
              label={t("end_date")}
              rules={[
                { required: true, message: t("select_end_date_msg") },
              ]}>
              <DatePicker
                style={{ width: "100%" }}
                format="DD/MM/YYYY"
                disabledDate={(current) => current.isBefore(dayjs(), "day")}
                placeholder={t("select_end_date_msg")}
              />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item
              name="pickup_time"
              label={t("tapping_time")}
              rules={[
                { required: true, message: t("select_tapping_time_msg") },
              ]}>
              <TimePicker
                format="HH:mm"
                style={{ width: "100%" }}
                placeholder={t("enter_tapping_time")}
              />
            </Form.Item>
          </Col>
        </Row>

        <Row justify="end" style={{ marginBottom: 16 }}>
          <Button
            type="primary"
            onClick={handleGeneratePlan}
            disabled={!expectedYield}>
            {t("generate_schedule")}
          </Button>
        </Row>

        <div
          style={{
            backgroundColor: "#fafafa",
            padding: 16,
            borderRadius: 8,
            marginTop: 16,
          }}>
          <Typography.Title level={5}>{t("schedule_detail")}</Typography.Title>
          <Form.List name="harvest_schedules">
            {(fields, { add, remove }) => (
              <>
                <Row gutter={8} style={{ marginBottom: 8, fontWeight: "bold" }}>
                  <Col span={6}>{t("tapping_date")}</Col>
                  <Col span={6}>{t("tapping_time_col")}</Col>
                  <Col span={6}>{t("area")}</Col>
                  <Col span={6}>{t("yield_kg")}</Col>
                  <Col span={2}></Col>
                </Row>

                {fields.map(({ key, name, ...restField }) => (
                  <Row gutter={8} key={key} style={{ marginBottom: 8 }}>
                    <Col span={6}>
                      <Form.Item
                        {...restField}
                        name={[name, "pickup_date"]}
                        rules={[{ required: true }]}
                        style={{ marginBottom: 0 }}>
                        <DatePicker
                          style={{ width: "100%" }}
                          format="DD/MM/YYYY"
                        />
                      </Form.Item>
                    </Col>
                    <Col span={6}>
                      <Form.Item
                        {...restField}
                        name={[name, "pickup_time"]}
                        rules={[{ required: true }]}
                        style={{ marginBottom: 0 }}>
                        <TimePicker style={{ width: "100%" }} format="HH:mm" />
                      </Form.Item>
                    </Col>
                    <Col span={6}>
                      <Form.Item
                        {...restField}
                        name={[name, "plot_id"]}
                        rules={[{ required: true }]}
                        style={{ marginBottom: 0 }}>
                        <InfiniteScrollSelect<IPlotByTransactionTicket>
                          queryKey={[
                            "lands-transaction-select",
                            ticketCode || "",
                          ]}
                          fetchFn={(params) =>
                            getLandByTransactionTicket({
                              ...params,
                              limit: 100,
                              transaction_ticket_code: ticketCode!,
                            })
                          }
                          mapOption={mapLandOptions}
                          allowClear
                          placeholder={t("select_area")}
                        />
                      </Form.Item>
                    </Col>
                    <Col span={3}>
                      <Form.Item
                        {...restField}
                        name={[name, "expected_yield"]}
                        rules={[{ required: true }]}
                        style={{ marginBottom: 0 }}>
                        <InputNumber style={{ width: "100%" }} />
                      </Form.Item>
                    </Col>
                    <Col
                      span={3}
                      style={{ display: "flex", alignItems: "center" }}>
                      {tappingRegime === "F" && fields.length > 1 && (
                        <MinusCircleOutlined
                          onClick={() => remove(name)}
                          style={{ color: "red", fontSize: 18 }}
                        />
                      )}
                    </Col>
                  </Row>
                ))}

                {tappingRegime === "F" && (
                  <Form.Item style={{ marginTop: 16 }}>
                    <Button
                      type="dashed"
                      onClick={() => add()}
                      block
                      icon={<PlusOutlined />}>
                      {t("add_tapping_date")}
                    </Button>
                  </Form.Item>
                )}
              </>
            )}
          </Form.List>
        </div>
      </Form>
    </BaseSheet>
  );
};

export default PlanSheet;
