"use client";
import React from "react";
import dayjs from "dayjs";
import { useQuery } from "@tanstack/react-query";
import {
  cancelTransactionTicket,
  confirmTransactionTicket,
  getTransactionTikets,
  IGetTransactionTicketParams,
} from "../../actions";
import { ITransactionTicket } from "../../types";
import {
  Flex,
  Space,
  Tag,
  Row,
  Col,
  Card,
  Button,
  Typography,
  Spin,
  Empty,
  Pagination,
  message,
  Popconfirm,
} from "antd";
import PurchaseFilter from "./purchase-filter";
import { useRouter } from "nextjs-toploader/app";
import { formatDateDDMMYYYY, formatVnCurrency } from "@/lib/utils";
import { TooltipButton } from "@/components/tooltip-button";
import {
  CheckOutlined,
  DeleteOutlined,
  EditOutlined,
  EyeOutlined,
  PlusOutlined,
} from "@ant-design/icons";
import PurchaseForm from "./purchase-form";
import { handleApiError } from "@/lib/api-error";
import TransactionTicketDetailModal from "../../components/transaction-ticket-detail-modal";
import { usePermissions } from "@/contexts/permission-context";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";

const { Text } = Typography;

import { useTranslations } from "next-intl";

const Purchase = () => {
  const t = useTranslations("TransactionTicket");
  const tCommon = useTranslations("Common");
  const router = useRouter();
  const [params, setParams] = React.useState<
    Partial<IGetTransactionTicketParams>
  >({
    page: 1,
    limit: 9,
    transaction_ticket_type: "purchase",
    status: "all",
    account_type: "farmer",
    start_date: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
    end_date: dayjs().format("YYYY-MM-DD"),
  });
  const { transactionTicket } = usePermissions();

  const [openPurchaseForm, setOpenPurchaseForm] = React.useState(false);
  const [purchase, setPurchase] = React.useState<ITransactionTicket | null>(
    null,
  );

  const [openDetailModal, setOpenDetailModal] = React.useState(false);
  const [selectedTicketCode, setSelectedTicketCode] = React.useState("");

  const { data, isFetching, refetch } = useQuery({
    queryKey: ["transaction-ticket-purchase", params],
    queryFn: () => getTransactionTikets(params),
  });

  const getStatusColor = (status: string) => {
    switch (status) {
      case "completed":
        return "success";
      case "pending":
        return "default";
      case "confirmed":
        return "warning";
      case "cancelled":
        return "error";
      default:
        return "default";
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case "completed":
        return t("status.completed");
      case "pending":
        return t("status.pending");
      case "confirmed":
        return t("status.confirmed");
      case "cancelled":
        return t("status.cancelled");
      default:
        return status?.toUpperCase();
    }
  };

  const handleSearch = (newParams: Partial<IGetTransactionTicketParams>) => {
    setParams((prev) => ({
      ...prev,
      ...newParams,
      page: 1,
    }));
  };

  const handleConfirm = async (transaction_ticket_code: string) => {
    try {
      await confirmTransactionTicket({
        transaction_ticket_type: "purchase",
        transaction_ticket_code,
      });
      message.success(t("purchase.confirm_success"));
      refetch();
    } catch (error) {
      handleApiError(error);
    }
  };

  const handleCancel = async (transaction_ticket_code: string) => {
    try {
      await cancelTransactionTicket({
        transaction_ticket_type: "purchase",
        transaction_ticket_code,
      });
      message.success(t("purchase.cancel_success"));
      refetch();
    } catch (error) {
      handleApiError(error);
    }
  };

  if (openPurchaseForm) {
    return (
      <PurchaseForm
        refetch={refetch}
        purchase={purchase}
        onBack={() => setOpenPurchaseForm(false)}
      />
    );
  }

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <Flex align="center" justify="space-between">
        <PurchaseFilter onSearch={handleSearch} />

        {(transactionTicket.full ||
          transactionTicket.purchase.create ||
          transactionTicket.purchase.full) && (
          <TooltipButton
            tooltip={t("purchase.add_button")}
            icon={<PlusOutlined />}
            type="primary"
            onClick={() => {
              setOpenPurchaseForm(true);
              setPurchase(null);
            }}>
            {tCommon("add")}
          </TooltipButton>
        )}
      </Flex>

      <Spin spinning={isFetching}>
        {(!data?.data || data.data.records.length === 0) && !isFetching ? (
          <Empty description={t("purchase.no_data")} />
        ) : (
          <Flex vertical>
            <Row gutter={[16, 16]} align="stretch">
              {data?.data?.records?.map((ticket: ITransactionTicket) => {
                const totalAmount =
                  (ticket.latex_total_amount || 0) +
                  (ticket.scrap_rubber_total_amount || 0);

                return (
                  <Col
                    xs={24}
                    sm={24}
                    md={12}
                    lg={8}
                    key={ticket.transaction_ticket_id}
                    style={{ display: "flex" }}>
                    <Card
                      style={{
                        width: "100%",
                        display: "flex",
                        flexDirection: "column",
                      }}
                      styles={{
                        body: {
                          flex: 1,
                          padding: "16px",
                          display: "flex",
                          flexDirection: "column",
                          gap: "12px",
                        },
                      }}>
                      <Flex vertical gap="small">
                        <Text strong style={{ fontSize: "16px", opacity: 0.8 }}>
                          {ticket.transaction_ticket_code.toLocaleUpperCase()}
                        </Text>

                        <Text style={{ fontSize: "14px", opacity: 0.8 }}>
                          {ticket.contract_code}
                        </Text>
                        <Space>
                          {ticket.status === "pending" ? (
                            <>
                              {(transactionTicket.full ||
                                transactionTicket.purchase.update) && (
                                <ConfirmTooltipButton
                                  confirmTitle={t(
                                    "purchase.confirm_delete_title",
                                  )}
                                  confirmDescription={t(
                                    "purchase.confirm_delete_description",
                                    { code: ticket.transaction_ticket_code },
                                  )}
                                  tooltip={tCommon("confirm")}
                                  type="primary"
                                  icon={<CheckOutlined />}
                                  onConfirm={() =>
                                    handleConfirm(
                                      ticket.transaction_ticket_code,
                                    )
                                  }
                                />
                              )}

                              {(transactionTicket.full ||
                                transactionTicket.purchase.update) && (
                                <ConfirmTooltipButton
                                  confirmTitle={t(
                                    "purchase.confirm_cancel_title",
                                  )}
                                  confirmDescription={t(
                                    "purchase.confirm_cancel_description",
                                    { code: ticket.transaction_ticket_code },
                                  )}
                                  tooltip={t("purchase.confirm_cancel_title")}
                                  type="primary"
                                  danger
                                  ghost
                                  icon={<DeleteOutlined />}
                                  onConfirm={() =>
                                    handleCancel(ticket.transaction_ticket_code)
                                  }
                                />
                              )}

                              {(transactionTicket.full ||
                                transactionTicket.purchase.update) && (
                                <TooltipButton
                                  tooltip={tCommon("edit")}
                                  type="primary"
                                  ghost
                                  icon={<EditOutlined />}
                                  onClick={() => {
                                    setOpenPurchaseForm(true);
                                    setPurchase(ticket);
                                  }}
                                />
                              )}
                            </>
                          ) : (
                            <TooltipButton
                              tooltip={tCommon("view_detail")}
                              type="dashed"
                              icon={<EyeOutlined />}
                              onClick={() => {
                                setOpenDetailModal(true);
                                setSelectedTicketCode(
                                  ticket.transaction_ticket_code,
                                );
                              }}
                            />
                          )}
                        </Space>
                      </Flex>

                      {/* Section 1: Thông tin 2 bên */}
                      <Row gutter={8}>
                        <Col span={12}>
                          <div
                            style={{
                              border: "1px solid var(--ant-color-border)",
                              borderRadius: "8px",
                              padding: "12px",
                              height: "100%",
                            }}>
                            <Text
                              strong
                              style={{ display: "block", marginBottom: "8px" }}>
                              {t("seller_info")}
                            </Text>
                            <Space
                              orientation="vertical"
                              size={2}
                              style={{
                                fontSize: "13px",
                                opacity: 0.8,
                                display: "flex",
                              }}>
                              <Text>
                                {t("seller")}: {ticket.seller_name}
                              </Text>
                              <Text>
                                {tCommon("phone_number")}: {ticket.seller_phone}
                              </Text>
                              <Text>
                                {t("account_type")}:{" "}
                                {ticket.seller_account_type}
                              </Text>
                              <Text>
                                {t("company")}:{" "}
                                {ticket.seller_company_short_name ||
                                  ticket.seller_company_id}
                              </Text>
                              <Text>
                                {t("address_label")}: {ticket.seller_address}
                              </Text>
                            </Space>
                          </div>
                        </Col>
                        <Col span={12}>
                          <div
                            style={{
                              border: "1px solid var(--ant-color-border)",
                              borderRadius: "8px",
                              padding: "12px",
                              height: "100%",
                            }}>
                            <Text
                              strong
                              style={{ display: "block", marginBottom: "8px" }}>
                              {t("buyer_info")}
                            </Text>
                            <Space
                              orientation="vertical"
                              size={2}
                              style={{
                                fontSize: "13px",
                                opacity: 0.8,
                                display: "flex",
                              }}>
                              <Text>
                                {t("buyer")}: {ticket.buyer_name}
                              </Text>
                              <Text>
                                {tCommon("phone_number")}: {ticket.buyer_phone}
                              </Text>
                              <Text>
                                {t("account_type")}: {ticket.buyer_account_type}
                              </Text>
                              <Text>
                                {t("company")}:{" "}
                                {ticket.buyer_company_short_name ||
                                  ticket.buyer_company_id}
                              </Text>
                              <Text>
                                {t("address_label")}: {ticket.buyer_address}
                              </Text>
                            </Space>
                          </div>
                        </Col>
                      </Row>

                      {/* Section 2: Thông tin Hàng hoá */}
                      <Row gutter={8}>
                        <Col span={12}>
                          <div
                            style={{
                              border: "1px solid var(--ant-color-border)",
                              borderRadius: "8px",
                              padding: "12px",
                              height: "100%",
                            }}>
                            <Text
                              strong
                              style={{ display: "block", marginBottom: "8px" }}>
                              {t("latex")}
                            </Text>
                            <Space
                              orientation="vertical"
                              size={2}
                              style={{
                                fontSize: "13px",
                                opacity: 0.8,
                                display: "flex",
                              }}>
                              <Text>
                                {t("weight")}: {ticket.latex_weight} kg
                              </Text>
                              <Text>TSC: {ticket.latex_tsc_grade}</Text>
                              <Text>
                                {t("unit_price")}:{" "}
                                {formatVnCurrency(ticket.latex_price_per_tsc)}
                              </Text>
                            </Space>
                          </div>
                        </Col>
                        <Col span={12}>
                          <div
                            style={{
                              border: "1px solid var(--ant-color-border)",
                              borderRadius: "8px",
                              padding: "12px",
                              height: "100%",
                            }}>
                            <Text
                              strong
                              style={{ display: "block", marginBottom: "8px" }}>
                              {t("scrap_rubber")}
                            </Text>
                            <Space
                              orientation="vertical"
                              size={2}
                              style={{
                                fontSize: "13px",
                                opacity: 0.8,
                                display: "flex",
                              }}>
                              <Text>
                                {t("weight")}: {ticket.scrap_rubber_weight} kg
                              </Text>
                              <Text>DRC: {ticket.scrap_rubber_drc_grade}</Text>
                              <Text>
                                {t("unit_price")}:{" "}
                                {formatVnCurrency(
                                  ticket.scrap_rubber_price_per_drc,
                                )}
                              </Text>
                            </Space>
                          </div>
                        </Col>
                      </Row>

                      <div style={{ flexGrow: 1 }} />

                      <Flex justify="center" style={{ margin: "8px 0" }}>
                        <Text
                          strong
                          style={{
                            fontSize: "16px",
                            color: "var(--ant-color-success)",
                          }}>
                          {t("total_amount")} {formatVnCurrency(totalAmount)}
                        </Text>
                      </Flex>

                      <Flex justify="space-between" align="flex-end">
                        <Space orientation="vertical" size={4}>
                          <Text type="secondary" style={{ fontSize: "12px" }}>
                            {t("sent_date")}
                          </Text>
                          <Text type="secondary" style={{ fontSize: "12px" }}>
                            {tCommon("status")}
                          </Text>
                        </Space>
                        <Space orientation="vertical" size={4} align="end">
                          <Text type="secondary" style={{ fontSize: "12px" }}>
                            {formatDateDDMMYYYY(ticket.created_at)}
                          </Text>
                          <Tag
                            color={getStatusColor(ticket.status)}
                            style={{ margin: 0, fontWeight: 600 }}>
                            {getStatusLabel(ticket.status)}
                          </Tag>
                        </Space>
                      </Flex>
                    </Card>
                  </Col>
                );
              })}
            </Row>

            {/* Phân trang */}
            {data && data?.data.total_records > 0 && (
              <Flex justify="flex-end">
                <Pagination
                  current={+data?.data.current_page}
                  total={+data?.data.total_records}
                  pageSize={+data?.data.page_limit}
                  showSizeChanger
                  onChange={(page, limit) => {
                    setParams((prev) => ({
                      ...prev,
                      page,
                      limit,
                    }));
                  }}
                />
              </Flex>
            )}
          </Flex>
        )}
      </Spin>

      <TransactionTicketDetailModal
        open={openDetailModal}
        onClose={() => setOpenDetailModal(false)}
        transactionTicketCode={selectedTicketCode}
        transactionType="purchase"
      />
    </Space>
  );
};

export default Purchase;
