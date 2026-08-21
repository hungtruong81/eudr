import { BaseFilter } from "@/components/base-filter";
import { DatePicker, Form, Input, Select } from "antd";
import React, { useEffect } from "react";
import {
  getTransactionTikets,
  IGetTransactionTicketParams,
} from "../../actions";
import dayjs from "dayjs";
import { rangePresets } from "@/constants/range-date";
import { useUser } from "@/providers/user-context";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getUserCompany } from "@/features/manage/user/actions";
import { IUserCompany } from "@/features/manage/group-permission/types";
import { usePermissions } from "@/contexts/permission-context";
import { ITransactionTicket } from "../../types";

const { RangePicker } = DatePicker;

interface ISaleFilterProps {
  onSearch: (params: Partial<IGetTransactionTicketParams>) => void;
}

import { useTranslations } from "next-intl";

const SaleFilter = ({ onSearch }: ISaleFilterProps) => {
  const t = useTranslations("TransactionTicket");
  const tCommon = useTranslations("Common");
  const [form] = Form.useForm();
  const { company } = usePermissions();

  const handleFinish = (values: any) => {
    const { dateRange, ...rest } = values;
    onSearch({
      ...rest,
      start_date: dateRange?.[0]?.format("YYYY-MM-DD") || "",
      end_date: dateRange?.[1]?.format("YYYY-MM-DD") || "",
    });
  };

  const handleReset = () => {
    form.resetFields();
    onSearch({
      search: "",
      status: "all",
      account_type: "farmer",
      start_date: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
      end_date: dayjs().format("YYYY-MM-DD"),
    });
  };

  useEffect(() => {
    form.setFieldsValue({
      dateRange: [dayjs().subtract(1, "month"), dayjs()],
    });
  }, [form]);

  return (
    <BaseFilter form={form} onFinish={handleFinish} onReset={handleReset}>
      <Form.Item name="search" className="mb-0" label={tCommon("search")}>
        <Input placeholder={tCommon("search_placeholder")} allowClear />
      </Form.Item>
      <Form.Item
        name="status"
        className="mb-0"
        initialValue="all"
        label={tCommon("status")}>
        <Select
          placeholder={tCommon("status")}
          style={{ width: 160 }}
          options={[
            { label: tCommon("all"), value: "all" },
            { label: t("status.pending"), value: "pending" },
            { label: t("status.cancelled"), value: "cancelled" },
            { label: t("status.completed"), value: "completed" },
          ]}
        />
      </Form.Item>
      <Form.Item name="contract_code" className="mb-0" label={t("contract")}>
        <InfiniteScrollSelect<ITransactionTicket>
          queryKey={["contract_code"]}
          placeholder={t("select_contract")}
          fetchFn={(params) =>
            getTransactionTikets({
              ...params,
              transaction_ticket_type: "sale",
              status: "all",
              account_type: "all",
            })
          }
          mapOption={(item) => ({
            value: String(item.contract_code),
            label: item.contract_code,
          })}
        />
      </Form.Item>
      {(company.full || company.view) && (
        <Form.Item name="member_user_id" className="mb-0" label={t("member")}>
          <InfiniteScrollSelect<IUserCompany>
            queryKey={["member_user_id"]}
            placeholder={t("select_member")}
            fetchFn={(params) => getUserCompany({ ...params })}
            mapOption={(item) => ({
              value: String(item.user_id),
              label: item.full_name,
            })}
          />
        </Form.Item>
      )}

      <Form.Item
        name="account_type"
        className="mb-0"
        initialValue="farmer"
        label={t("account_type_label")}>
        <Select
          placeholder={t("account_type_label")}
          style={{ width: 150 }}
          allowClear
          options={[
            { label: t("farmer"), value: "farmer" },
            { label: t("purchaser"), value: "purchaser" },
          ]}
        />
      </Form.Item>
      <Form.Item
        name="dateRange"
        className="mb-0"
        initialValue={[dayjs().subtract(1, "month"), dayjs()]}
        label={tCommon("creation_date")}>
        <RangePicker
          placeholder={[tCommon("from_date"), tCommon("to_date")]}
          format="DD/MM/YYYY"
          presets={rangePresets}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default SaleFilter;
