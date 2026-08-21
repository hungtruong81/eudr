import {
  Button,
  Card,
  Empty,
  Flex,
  Form,
  InputNumber,
  Segmented,
  Space,
  Tag,
  Typography,
  message,
} from "antd";
import { useEffect, useMemo, useState } from "react";
import { QrScannerInput } from "../shared/qr-scanner-input";
import {
  DeleteOutlined,
  FireOutlined,
  LoginOutlined,
  LogoutOutlined,
  QrcodeOutlined,
} from "@ant-design/icons";
import {
  DriedPole,
  PoleAssignment,
  RSS_OVEN_OPTIONS,
  RssQuality,
  RssWorkflowPlan,
  TrolleyBatch,
  getRssQualityLabel,
} from "../shared/rss-process-state";
import {
  RssQualityPicker,
  RssTechnicalDetails,
  RssTilePicker,
} from "../shared/rss-selection";

const { Text } = Typography;

interface DryingStationProps {
  workflowPlan?: RssWorkflowPlan | null;
  trolleyBatches: TrolleyBatch[];
  driedPoles: DriedPole[];
  onMoveTrolleyToOven: (trolleyCode: string, ovenCode: string) => void;
  onCreateDriedPoles: (
    trolleyCode: string,
    ovenCode: string,
    poles: DriedPole[],
  ) => void;
}

interface OvenInFormValues {
  trolleyCode: string;
  ovenCode: string;
}

interface OvenOutFormValues {
  trolleyCode: string;
  grade: RssQuality;
  sheetCountAfterDrying?: number;
}

interface OutTroughGroup {
  troughCode: string;
  quality: RssQuality;
  sheetCount: number;
  assignments: PoleAssignment[];
}

const getInitialTroughSheetCounts = (assignments: PoleAssignment[]) =>
  assignments.reduce<Record<string, number>>((counts, assignment) => {
    counts[assignment.troughCode] =
      (counts[assignment.troughCode] ?? 0) + assignment.sheetCount;
    return counts;
  }, {});

const getTrolleyQuality = (trolley: TrolleyBatch): RssQuality => {
  const qualities = new Set(trolley.assignments.map((item) => item.quality));

  if (qualities.size === 0) return "NA";
  if (qualities.size > 1) return "Mix";
  return Array.from(qualities)[0] ?? "NA";
};

export const DryingStation = ({
  workflowPlan,
  trolleyBatches,
  driedPoles,
  onMoveTrolleyToOven,
  onCreateDriedPoles,
}: DryingStationProps) => {
  const [formIn] = Form.useForm<OvenInFormValues>();
  const [formOut] = Form.useForm<OvenOutFormValues>();
  const [mode, setMode] = useState<"in" | "out">("in");
  const [outTroughSheetCounts, setOutTroughSheetCounts] = useState<
    Record<string, number>
  >({});

  const loadedTrolleys = useMemo(
    () => trolleyBatches.filter((batch) => batch.status === "loaded"),
    [trolleyBatches],
  );

  const dryingTrolleys = useMemo(
    () => trolleyBatches.filter((batch) => batch.status === "drying"),
    [trolleyBatches],
  );

  const trolleyInCode = Form.useWatch("trolleyCode", formIn);
  const ovenInCode = Form.useWatch("ovenCode", formIn);
  const trolleyOutCode = Form.useWatch("trolleyCode", formOut);
  const outGrade =
    Form.useWatch("grade", formOut) ?? workflowPlan?.quality ?? "NA";

  const selectedTrolleyIn = useMemo(
    () => loadedTrolleys.find((batch) => batch.trolleyCode === trolleyInCode),
    [loadedTrolleys, trolleyInCode],
  );

  const selectedTrolleyOut = useMemo(
    () => dryingTrolleys.find((batch) => batch.trolleyCode === trolleyOutCode),
    [dryingTrolleys, trolleyOutCode],
  );

  const loadedTrolleyOptions = useMemo(
    () =>
      loadedTrolleys.map((batch) => ({
        label: batch.trolleyCode,
        value: batch.trolleyCode,
        description: `${batch.assignments.length} sào · ${getRssQualityLabel(
          getTrolleyQuality(batch),
        )}`,
      })),
    [loadedTrolleys],
  );

  const dryingTrolleyOptions = useMemo(
    () =>
      dryingTrolleys.map((batch) => ({
        label: batch.trolleyCode,
        value: batch.trolleyCode,
        description: `${batch.assignments.length} sào · Lò ${
          batch.ovenCode ?? "NA"
        }`,
      })),
    [dryingTrolleys],
  );

  const ovenOptions = useMemo(
    () =>
      RSS_OVEN_OPTIONS.map((ovenCode) => ({
        label: ovenCode,
        value: ovenCode,
        description:
          workflowPlan?.ovenCode === ovenCode ? "Có trong lệnh" : "Sẵn sàng",
        meta: workflowPlan?.ovenCode === ovenCode ? "Theo lệnh" : undefined,
      })),
    [workflowPlan],
  );

  const selectedTrolleyOutTroughs = useMemo<OutTroughGroup[]>(() => {
    if (!selectedTrolleyOut) return [];

    const groups = new Map<string, OutTroughGroup>();

    selectedTrolleyOut.assignments.forEach((assignment) => {
      const current = groups.get(assignment.troughCode);

      if (!current) {
        groups.set(assignment.troughCode, {
          troughCode: assignment.troughCode,
          quality: assignment.quality,
          sheetCount: assignment.sheetCount,
          assignments: [assignment],
        });
        return;
      }

      current.sheetCount += assignment.sheetCount;
      current.assignments.push(assignment);
      if (current.quality !== assignment.quality) {
        current.quality = "Mix";
      }
    });

    return Array.from(groups.values());
  }, [selectedTrolleyOut]);

  const selectedOutTroughs = useMemo(() => {
    const knownGroups = selectedTrolleyOutTroughs.filter(
      (group) => outTroughSheetCounts[group.troughCode] !== undefined,
    );
    const knownCodes = new Set(knownGroups.map((group) => group.troughCode));
    const manualGroups = Object.entries(outTroughSheetCounts)
      .filter(([troughCode]) => !knownCodes.has(troughCode))
      .map(
        ([troughCode, sheetCount]) =>
          ({
            troughCode,
            quality: "NA",
            sheetCount,
            assignments: [
              {
                id: `${trolleyOutCode || "GG-DRAFT"}-${troughCode}-S01`,
                poleCode: "Sào 01",
                troughCode,
                rolledBatchId: `ROLL-${troughCode}`,
                sheetCount,
                quality: "NA",
              },
            ],
          }) satisfies OutTroughGroup,
      );

    return [...knownGroups, ...manualGroups];
  }, [outTroughSheetCounts, selectedTrolleyOutTroughs, trolleyOutCode]);

  const outTroughOptions = useMemo(
    () =>
      selectedTrolleyOutTroughs.map((group) => ({
        value: group.troughCode,
        label: `Mương ${group.troughCode}`,
        description: `${group.sheetCount} tờ · ${group.assignments.length} sào`,
        meta: getRssQualityLabel(group.quality),
      })),
    [selectedTrolleyOutTroughs],
  );

  const selectedOutTroughCodes = useMemo(
    () => Object.keys(outTroughSheetCounts),
    [outTroughSheetCounts],
  );

  useEffect(() => {
    if (!workflowPlan) return;

    formIn.setFieldsValue({
      trolleyCode: workflowPlan.trolleyCode,
      ovenCode: workflowPlan.ovenCode,
    });
    formOut.setFieldsValue({
      trolleyCode: workflowPlan.trolleyCode,
      grade: workflowPlan.quality,
      sheetCountAfterDrying: workflowPlan.defaultSheetCount,
    });
  }, [formIn, formOut, workflowPlan]);

  const handleScanTrolleyIn = (value: string) => {
    const found = loadedTrolleys.find(
      (batch) => batch.trolleyCode.toLowerCase() === value.toLowerCase(),
    );

    formIn.setFieldsValue({ trolleyCode: found?.trolleyCode ?? value });
    message.success(`Đã chọn xe gòong ${found?.trolleyCode ?? value} đưa vào lò`);
  };

  const handleSelectTrolleyOut = (value: string) => {
    const found = dryingTrolleys.find(
      (batch) => batch.trolleyCode.toLowerCase() === value.toLowerCase(),
    );

    const initialTroughSheetCounts = getInitialTroughSheetCounts(
      found?.assignments ?? [],
    );

    formOut.setFieldsValue({
      trolleyCode: found?.trolleyCode ?? value,
      grade: found ? getTrolleyQuality(found) : workflowPlan?.quality ?? "NA",
      sheetCountAfterDrying: Object.values(initialTroughSheetCounts)[0] ?? 1,
    });
    setOutTroughSheetCounts(initialTroughSheetCounts);
    message.success(`Đã chọn xe gòong ${found?.trolleyCode ?? value} ra lò`);
  };

  const handleScanTroughOut = (value: string) => {
    const found = selectedTrolleyOutTroughs.find(
      (group) => group.troughCode.toLowerCase() === value.toLowerCase(),
    );

    const nextSheetCount = Math.max(
      1,
      Number(
        formOut.getFieldValue("sheetCountAfterDrying") ??
          found?.sheetCount ??
          1,
      ),
    );

    setOutTroughSheetCounts((prev) => ({
      ...prev,
      [found?.troughCode ?? value]: nextSheetCount,
    }));
    formOut.setFieldsValue({ sheetCountAfterDrying: nextSheetCount });
    message.success(`Đã thêm mương ${found?.troughCode ?? value} ra lò`);
  };

  const handleChangeOutTroughSheetCount = (
    troughCode: string,
    value: number | null,
  ) => {
    setOutTroughSheetCounts((prev) => ({
      ...prev,
      [troughCode]: Math.max(1, value ?? 1),
    }));
  };

  const handleRemoveOutTrough = (troughCode: string) => {
    setOutTroughSheetCounts((prev) => {
      const next = { ...prev };
      delete next[troughCode];
      return next;
    });
  };

  const handleSelectOutTroughs = (troughCodes: string[]) => {
    setOutTroughSheetCounts((prev) =>
      troughCodes.reduce<Record<string, number>>((counts, troughCode) => {
        const found = selectedTrolleyOutTroughs.find(
          (group) => group.troughCode === troughCode,
        );

        counts[troughCode] = prev[troughCode] ?? found?.sheetCount ?? 1;
        return counts;
      }, {}),
    );
  };

  const handleFinishIn = (values: OvenInFormValues) => {
    onMoveTrolleyToOven(values.trolleyCode, values.ovenCode);
    formIn.resetFields();
    message.success(
      `Đã kéo nguyên xe ${values.trolleyCode} vào lò ${values.ovenCode}`,
    );
  };

  const handleFinishOut = (values: OvenOutFormValues) => {
    const trolley = dryingTrolleys.find(
      (batch) => batch.trolleyCode === values.trolleyCode,
    );

    if (selectedOutTroughs.length === 0) {
      message.error("Vui lòng chọn ít nhất một mương ra lò");
      return;
    }

    const poles: DriedPole[] = selectedOutTroughs.flatMap((group) => {
      const totalSheets = Math.max(
        1,
        outTroughSheetCounts[group.troughCode] ?? group.sheetCount,
      );
      const baseSheetCount = Math.floor(
        totalSheets / Math.max(1, group.assignments.length),
      );
      const remainder = totalSheets % Math.max(1, group.assignments.length);

      return group.assignments.map((assignment, index) => {
        const sheetCount = baseSheetCount + (index < remainder ? 1 : 0);

        return {
          ...assignment,
          sheetCount,
          trolleyCode: values.trolleyCode,
          ovenCode: trolley?.ovenCode ?? "NA",
          grade: values.grade,
          driedWeightKg: sheetCount,
          driedAt: "Vừa tạo",
        };
      });
    });

    onCreateDriedPoles(values.trolleyCode, trolley?.ovenCode ?? "NA", poles);
    formOut.resetFields();
    setOutTroughSheetCounts({});
    message.success(
      `Đã ghi nhận ${selectedOutTroughs.length} mương ra lò trên xe ${values.trolleyCode}`,
    );
  };

  return (
    <Card
      className="rss-station-card"
      title={
        <Space>
          <FireOutlined style={{ color: "#fa8c16" }} />
          <span>Sấy khô nguyên xe gòong</span>
        </Space>
      }>
      <Space orientation="vertical" style={{ width: "100%" }} size="large">
        <RssTilePicker<"in" | "out">
          ariaLabel="Chọn công đoạn sấy"
          selectedValue={mode}
          options={[
            {
              value: "in",
              label: (
                <Space>
                  <LoginOutlined />
                  <span>1. Vào lò</span>
                </Space>
              ),
              description: "Kéo xe gòong vào lò",
            },
            {
              value: "out",
              label: (
                <Space>
                  <LogoutOutlined />
                  <span>2. Ra lò</span>
                </Space>
              ),
              description: "Ghi nhận ra lò sấy chín",
            },
          ]}
          onChange={(value) => setMode(value as "in" | "out")}
        />
        {mode === "in" ? (
          <Card size="small">
            <Space
              orientation="vertical"
              style={{ width: "100%" }}
              size="middle">
              <QrScannerInput
                placeholder="Quét mã QR xe gòong vào lò…"
                onScan={handleScanTrolleyIn}
              />

              <Form
                form={formIn}
                layout="vertical"
                size="large"
                onFinish={handleFinishIn}>
                <Form.Item
                  name="trolleyCode"
                  rules={[
                    { required: true, message: "Vui lòng chọn xe gòong" },
                  ]}
                  hidden>
                  <input type="hidden" />
                </Form.Item>

                <Form.Item
                  name="ovenCode"
                  initialValue="LO-01"
                  rules={[{ required: true, message: "Vui lòng chọn lò" }]}
                  hidden>
                  <input type="hidden" />
                </Form.Item>

                <Form.Item label="Xe gòong đưa nguyên xe vào lò" required>
                  <RssTilePicker
                    ariaLabel="Chọn xe gòong chờ sấy"
                    selectedValue={trolleyInCode}
                    options={loadedTrolleyOptions}
                    emptyText="Chưa có xe gòong chờ sấy"
                    onChange={(value) =>
                      formIn.setFieldsValue({ trolleyCode: value as string })
                    }
                  />
                </Form.Item>

                <Form.Item label="Lò sấy" required>
                  <RssTilePicker
                    ariaLabel="Chọn lò sấy"
                    selectedValue={ovenInCode ?? workflowPlan?.ovenCode ?? "LO-01"}
                    options={ovenOptions}
                    onChange={(value) =>
                      formIn.setFieldsValue({ ovenCode: value as string })
                    }
                  />
                </Form.Item>

                {selectedTrolleyIn ? (
                  <PolePreview trolley={selectedTrolleyIn} />
                ) : null}

                <Button
                  type="primary"
                  htmlType="submit"
                  block
                  className="rss-mobile-primary-action">
                  Xác nhận kéo xe vào lò
                </Button>
              </Form>
            </Space>
          </Card>
        ) : (
          <Card size="small" title="Scan xe gòong và mương ra lò">
            <Space
              orientation="vertical"
              style={{ width: "100%" }}
              size="middle">
              <QrScannerInput
                placeholder="Quét mã QR xe gòong ra lò…"
                onScan={handleSelectTrolleyOut}
              />

              <RssTilePicker
                ariaLabel="Chọn xe gòong đang sấy"
                selectedValue={trolleyOutCode}
                options={dryingTrolleyOptions}
                emptyText="Chưa có xe gòong đang sấy"
                onChange={(value) => handleSelectTrolleyOut(value as string)}
              />

              {selectedTrolleyOut ? (
                <PolePreview trolley={selectedTrolleyOut} />
              ) : null}

              <QrScannerInput
                placeholder="Quét mã QR mương ra lò…"
                onScan={handleScanTroughOut}
              />

              <RssTilePicker
                ariaLabel="Chọn mương ra lò"
                multiple
                selectedValues={selectedOutTroughCodes}
                options={outTroughOptions}
                emptyText="Chọn xe gòong trước để hiện mương"
                onChange={(values) => handleSelectOutTroughs(values as string[])}
              />

              <Form
                form={formOut}
                layout="vertical"
                size="large"
                onFinish={handleFinishOut}>
                <Form.Item
                  name="trolleyCode"
                  rules={[
                    { required: true, message: "Vui lòng chọn xe gòong" },
                  ]}
                  hidden>
                  <input type="hidden" />
                </Form.Item>

                <Form.Item
                  name="grade"
                  initialValue="NA"
                  rules={[
                    { required: true, message: "Vui lòng chọn chất lượng" },
                  ]}
                  hidden>
                  <input type="hidden" />
                </Form.Item>

                <Form.Item
                  name="sheetCountAfterDrying"
                  label="Số tờ mủ sau khi sấy xong"
                  initialValue={1}
                  rules={[
                    { required: true, message: "Vui lòng nhập số tờ mủ" },
                  ]}>
                  <InputNumber min={1} style={{ width: "100%" }} />
                </Form.Item>

                <Card
                  size="small"
                  title={`Mương ra lò (${selectedOutTroughs.length})`}
                  style={{ backgroundColor: "#fafafa", marginBottom: 16 }}>
                  {selectedOutTroughs.length === 0 ? (
                    <Empty
                      image={Empty.PRESENTED_IMAGE_SIMPLE}
                      description="Chưa chọn mương nào"
                    />
                  ) : (
                    <Space
                      orientation="vertical"
                      style={{ width: "100%" }}
                      size="small">
                      {selectedOutTroughs.map((group) => (
                        <Card key={group.troughCode} size="small">
                          <Space
                            orientation="vertical"
                            style={{ width: "100%" }}
                            size="small">
                            <Flex
                              align="center"
                              justify="space-between"
                              gap="small"
                              wrap>
                              <Space orientation="vertical" size={0}>
                                <Text strong>Mương {group.troughCode}</Text>
                                <Text type="secondary">
                                  {group.assignments.length} sào ·{" "}
                                  {getRssQualityLabel(group.quality)}
                                </Text>
                              </Space>
                              <Button
                                type="text"
                                danger
                                icon={<DeleteOutlined />}
                                aria-label={`Xóa mương ${group.troughCode}`}
                                onClick={() =>
                                  handleRemoveOutTrough(group.troughCode)
                                }
                              />
                            </Flex>
                            <Space
                              orientation="vertical"
                              style={{ width: "100%" }}
                              size={4}>
                              <Text type="secondary">
                                Số tờ ra lò theo mương
                              </Text>
                              <InputNumber
                                min={1}
                                value={outTroughSheetCounts[group.troughCode]}
                                onChange={(value) =>
                                  handleChangeOutTroughSheetCount(
                                    group.troughCode,
                                    typeof value === "number"
                                      ? value
                                      : Number(value ?? 1),
                                  )
                                }
                                style={{ width: "100%" }}
                              />
                            </Space>
                          </Space>
                        </Card>
                      ))}
                    </Space>
                  )}
                </Card>

                <Form.Item label="Chất lượng sau sấy" required>
                  <RssQualityPicker
                    value={outGrade}
                    onChange={(grade) => formOut.setFieldsValue({ grade })}
                  />
                </Form.Item>

                <Button
                  type="primary"
                  htmlType="submit"
                  block
                  className="rss-mobile-primary-action">
                  Xác nhận xe ra lò
                </Button>
              </Form>
            </Space>
          </Card>
        )}
        <RssTechnicalDetails summary={`Sào đã sấy khô (${driedPoles.length})`}>
          {driedPoles.length === 0 ? (
            <Empty
              image={Empty.PRESENTED_IMAGE_SIMPLE}
              description="Chưa có sào đã sấy"
            />
          ) : (
            <div className="rss-record-list">
              {driedPoles.map((pole) => (
                <div key={pole.id} className="rss-touch-list-item">
                  <Flex align="center" justify="space-between" gap="small" wrap>
                    <Space orientation="vertical" size={0}>
                      <Text strong>
                        <QrcodeOutlined /> {pole.trolleyCode} / {pole.poleCode}
                      </Text>
                      <Text type="secondary">
                        Mương {pole.troughCode} · {pole.sheetCount} tờ ·{" "}
                        {pole.driedWeightKg} kg · Lò {pole.ovenCode}
                      </Text>
                    </Space>
                    <Tag color="green">{getRssQualityLabel(pole.grade)}</Tag>
                  </Flex>
                </div>
              ))}
            </div>
          )}
        </RssTechnicalDetails>
      </Space>
    </Card>
  );
};

const PolePreview = ({ trolley }: { trolley: TrolleyBatch }) => (
  <RssTechnicalDetails
    summary={`${trolley.trolleyCode} · ${trolley.assignments.length} sào`}>
    <Flex wrap="wrap" gap="small">
      {trolley.assignments.map((assignment) => (
        <Tag key={assignment.id} color="orange">
          {assignment.poleCode} · Mương {assignment.troughCode} ·{" "}
          {assignment.sheetCount} tờ · {getRssQualityLabel(assignment.quality)}
        </Tag>
      ))}
    </Flex>
  </RssTechnicalDetails>
);
