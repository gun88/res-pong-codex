import { Directive, EventEmitter, HostBinding, HostListener, Input, Output } from '@angular/core';
import { Router } from '@angular/router';

@Directive({
  selector: '[rpSwipeNav]',
  standalone: true
})
export class SwipeNavDirective {
  @Input() nextLink?: string | any[];
  @Input() prevLink?: string | any[];

  @Input() thresholdPx = 60;
  @Input() maxDurationMs = 600;

  @Output() swipeLeft = new EventEmitter<void>();
  @Output() swipeRight = new EventEmitter<void>();

  @HostBinding('style.touch-action') touchAction = 'pan-y'; // needed on iOS

  private startX = 0;
  private startY = 0;
  private startTime = 0;
  private tracking = false;

  constructor(private router: Router) {}

  // ===== Pointer fallback-safe =====
  @HostListener('pointerdown', ['$event'])
  onPointerDown(ev: PointerEvent) {
    if (ev.pointerType && ev.pointerType !== 'touch' && ev.pointerType !== 'pen') return;
    this.begin(ev.clientX, ev.clientY);
  }

  @HostListener('pointerup', ['$event'])
  onPointerUp(ev: PointerEvent) {
    if (!this.tracking) return;
    this.end(ev.clientX, ev.clientY);
  }

  @HostListener('pointercancel')
  onPointerCancel() {
    this.tracking = false;
  }

  // ===== Touch fallback (iOS/Android where pointer events may be limited) =====
  @HostListener('touchstart', ['$event'])
  onTouchStart(ev: TouchEvent) {
    if (this.tracking) return;
    const t = ev.touches[0];
    this.begin(t.clientX, t.clientY);
  }

  @HostListener('touchend', ['$event'])
  onTouchEnd(ev: TouchEvent) {
    if (!this.tracking) return;
    const t = ev.changedTouches[0];
    this.end(t.clientX, t.clientY);
  }

  private begin(x: number, y: number) {
    this.tracking = true;
    this.startX = x;
    this.startY = y;
    this.startTime = performance.now();
  }

  private end(x: number, y: number) {
    const dx = x - this.startX;
    const dy = y - this.startY;
    const dt = performance.now() - this.startTime;
    this.tracking = false;

    if (dt > this.maxDurationMs) return;
    if (Math.abs(dx) < this.thresholdPx) return;
    if (Math.abs(dy) > Math.abs(dx)) return;

    if (dx < 0) {
      this.swipeLeft.emit();
      if (this.nextLink) this.router.navigate(Array.isArray(this.nextLink) ? this.nextLink : [this.nextLink]);
    } else {
      this.swipeRight.emit();
      if (this.prevLink) this.router.navigate(Array.isArray(this.prevLink) ? this.prevLink : [this.prevLink]);
    }
  }
}
