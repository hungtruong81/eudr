"use client";

import {
  Spin,
  Space,
  Drawer,
  Button,
} from "antd";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import { useTranslations } from "next-intl";
import { getTransactionTicketDetail } from "../actions";
import TransactionTicketInvoice from "./transaction-ticket-invoice";

interface TransactionTicketDetailModalProps {
  open: boolean;
  onClose: () => void;
  transactionTicketCode: string;
  transactionType: "sale" | "purchase";
}

const TransactionTicketDetailModal: React.FC<
  TransactionTicketDetailModalProps
> = ({ open, onClose, transactionTicketCode, transactionType }) => {
  const t = useTranslations("TransactionTicket");
  const tc = useTranslations("Common");

  const { data: ticketDetail, isLoading: isTicketLoading } = useQuery({
    queryKey: ["transaction-ticket-detail", transactionTicketCode],
    queryFn: () =>
      getTransactionTicketDetail(transactionTicketCode, transactionType),
    enabled: open && !!transactionTicketCode,
  });

  const ticket = ticketDetail?.data;

  return (
    <Drawer
      open={open}
      onClose={onClose}
      footer={null}
      size={"100%"}
      title={t("invoice_title")}
      className="invoice-Drawer"
      extra={
        <Space>
          <Button onClick={onClose}>
            {tc("close")}
          </Button>
        </Space>
      }>
      <Spin spinning={isTicketLoading}>
        {ticket && <TransactionTicketInvoice ticket={ticket} />}
      </Spin>
    </Drawer>
  );
};

export default TransactionTicketDetailModal;
