import dayjs, { Dayjs } from "dayjs";

export const rangePresets: { label: string; value: [Dayjs, Dayjs] }[] = [
  { label: "Hôm nay", value: [dayjs(), dayjs()] },
  {
    label: "Hôm qua",
    value: [dayjs().subtract(1, "day"), dayjs().subtract(1, "day")],
  },
  { label: "Tuần", value: [dayjs().subtract(7, "day"), dayjs()] },
  { label: "Tháng", value: [dayjs().subtract(1, "month"), dayjs()] },
  { label: "Quý", value: [dayjs().subtract(3, "month"), dayjs()] },
  { label: "Năm", value: [dayjs().subtract(1, "year"), dayjs()] },
];
