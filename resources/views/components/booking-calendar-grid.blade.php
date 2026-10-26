            <div role="grid" class="mt-2" :aria-label="`Tanggal sewa, ${titleFor(calendarMonth)}`" @keydown="onKey($event)" @mouseleave="hoverKey = null" :class="{ 'opacity-60': calLoading }">
                <div role="row" class="{{ ($mobile ?? false) ? 'sr-only' : '' }} grid grid-cols-7 text-center text-[11px] font-semibold uppercase tracking-wide">
                    <template x-for="d in ['Sen','Sel','Rab','Kam','Jum','Sab','Min']" :key="d">
                        <span role="columnheader" class="py-1" x-text="d"></span>
                    </template>
                </div>
                <template x-for="(week, wi) in weeksFor(calendarMonth)" :key="`${calendarMonth}-${wi}`">
                    <div role="row" class="grid grid-cols-7">
                        <template x-for="cell in week" :key="cell.key">
                            <div role="gridcell" class="relative flex h-12 items-center justify-center lg:h-[2.125rem]" :aria-selected="cell.day ? String(isEdge(cell.key) || band(cell.key) === 'mid') : null">
                                <template x-if="cell.day">
                                    <div class="contents">
                                        <span class="bk-band" :class="`bk-band-${band(cell.key)}`" x-show="band(cell.key)" aria-hidden="true"></span>
                                        <button type="button" class="bk-day"
                                            :class="{ 'bk-day-edge': isEdge(cell.key), 'bk-day-off': isOff(cell.key), 'bk-day-today': cell.key === today }"
                                            :data-date="cell.key"
                                            :tabindex="cell.key === tabKeyFor(calendarMonth) ? 0 : -1"
                                            :aria-disabled="isOff(cell.key) ? 'true' : 'false'"
                                            :aria-label="label(cell.key)"
                                            :title="partialText(cell.key)"
                                            @click="pick(cell.key)"
                                            @mouseenter="hoverKey = cell.key"
                                            @focus="focusKey = cell.key; hoverKey = cell.key">
                                            <span x-text="cell.day"></span>
                                            <span class="bk-dot" x-show="isPartial(cell.key)" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

